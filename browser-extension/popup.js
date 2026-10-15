// Popup script - handles UI logic

// Firefox's `chrome` namespace is callback-based and returns nothing from
// promise-style calls; `browser` is its promise API. Chrome has no `browser`,
// so this resolves to the right one in both.
const api = globalThis.browser ?? chrome;

const API_BASE_URL = 'http://localhost:8000';

document.addEventListener('DOMContentLoaded', async () => {
    const elements = {
        loading: document.getElementById('loading'),
        error: document.getElementById('error'),
        noArticle: document.getElementById('no-article'),
        result: document.getElementById('result'),
        analyzeSection: document.getElementById('analyze-section'),
        analyzeBtn: document.getElementById('analyze-btn'),
        retryBtn: document.getElementById('retry-btn'),
        highlightBtn: document.getElementById('highlight-btn'),
        detailsBtn: document.getElementById('details-btn'),
        predictionBadge: document.getElementById('prediction-badge'),
        confidenceFill: document.getElementById('confidence-fill'),
        confidenceText: document.getElementById('confidence-text'),
        articleTitle: document.getElementById('article-title'),
        errorMessage: document.getElementById('error-message')
    };

    // Only the state containers get toggled. The buttons live inside them, so
    // hiding those too left an empty white card: hideAll() put .hidden on
    // #analyze-btn and nothing ever took it back off.
    const stateElements = [
        elements.loading,
        elements.error,
        elements.noArticle,
        elements.result,
        elements.analyzeSection
    ];

    // Hide all states
    function hideAll() {
        stateElements.forEach(el => {
            if (el) el.classList.add('hidden');
        });
    }

    // Show specific state
    function showState(stateName) {
        hideAll();
        if (elements[stateName]) {
            elements[stateName].classList.remove('hidden');
        }
    }

    // Display result
    function displayResult(result) {
        showState('result');

        // Set prediction badge
        const isFake = result.prediction === 'Fake';
        elements.predictionBadge.textContent = isFake ? 'FAKE NEWS' : 'REAL NEWS';
        elements.predictionBadge.className = `badge ${isFake ? 'badge-fake' : 'badge-real'}`;

        // Set confidence
        const confidence = Math.round(result.confidence_score * 100);
        elements.confidenceFill.style.width = `${confidence}%`;
        elements.confidenceFill.className = `progress-fill ${isFake ? 'fake' : 'real'}`;
        elements.confidenceText.textContent = `${confidence}%`;

        // Set article title
        if (result.title) {
            elements.articleTitle.textContent = result.title;
        }
    }

    // Call the Laravel app straight from the popup. This used to round-trip
    // through background.js, but MV3 background service workers do not run in
    // Firefox (extensions.backgroundServiceWorker.enabled is locked to false),
    // so on Firefox nobody ever answered the message.
    async function analyzeArticle(articleData) {
        let response;
        try {
            response = await fetch(`${API_BASE_URL}/api/extension/check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    text: articleData.text,
                    url: articleData.url,
                    title: articleData.title
                })
            });
        } catch (e) {
            throw new Error('Could not reach the NewsGuard server. Is "docker compose up -d" running, and is this extension allowed to access localhost?');
        }

        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }

        const data = await response.json();

        // Store result for the next time this page is opened
        await api.storage.local.set({
            lastResult: {
                ...data,
                timestamp: Date.now(),
                url: articleData.url
            }
        });

        return data;
    }

    // Analyze current page
    async function analyzePage() {
        try {
            showState('loading');

            const [tab] = await api.tabs.query({ active: true, currentWindow: true });
            if (!tab) {
                showState('noArticle');
                return;
            }

            // Content script is not injected on browser pages / PDFs
            let articleData = null;
            try {
                articleData = await api.tabs.sendMessage(tab.id, { action: 'extract' });
            } catch (e) {
                articleData = null;
            }

            if (!articleData || !articleData.text) {
                showState('noArticle');
                return;
            }

            const result = await analyzeArticle(articleData);
            displayResult(result);

            // Highlight the words in the page
            try {
                await api.tabs.sendMessage(tab.id, { action: 'highlight', result });
            } catch (e) {
                // page may have navigated away; result is still shown
            }
        } catch (error) {
            console.error('Analysis error:', error);
            showState('error');
            elements.errorMessage.textContent = error.message || 'Failed to analyze article. Please try again.';
        }
    }

    // Check for cached result
    const [tab] = await api.tabs.query({ active: true, currentWindow: true });
    if (!tab) {
        showState('noArticle');
    } else {
        const { lastResult } = await api.storage.local.get(['lastResult']);
        if (lastResult && lastResult.url === tab.url) {
            displayResult(lastResult);
        } else {
            showState('analyzeSection');
        }
    }

    // Event listeners
    elements.analyzeBtn?.addEventListener('click', analyzePage);
    elements.retryBtn?.addEventListener('click', analyzePage);

    elements.highlightBtn?.addEventListener('click', async () => {
        const [tab] = await api.tabs.query({ active: true, currentWindow: true });
        const result = await api.storage.local.get(['lastResult']);
        if (tab && result.lastResult) {
            await api.tabs.sendMessage(tab.id, {
                action: 'highlight',
                result: result.lastResult
            });
        }
    });

    elements.detailsBtn?.addEventListener('click', async () => {
        const result = await api.storage.local.get(['lastResult']);
        if (result.lastResult && result.lastResult.history_id) {
            api.tabs.create({
                url: `${API_BASE_URL}/history/${result.lastResult.history_id}`
            });
        }
    });
});