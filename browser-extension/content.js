// Content script - extracts article text from web pages

let analysisResult = null;

// Extract article text from the page
function extractArticleText() {
    // Try multiple selectors to find article content
    const selectors = [
        'article',
        '[role="article"]',
        '.article-content',
        '.post-content',
        '.entry-content',
        '.story-body',
        '.article-body',
        'main article',
        '#article-body',
        '.content-body'
    ];

    let articleElement = null;

    for (const selector of selectors) {
        articleElement = document.querySelector(selector);
        if (articleElement && articleElement.innerText.length > 200) {
            break;
        }
    }

    // Fallback: get main content
    if (!articleElement || articleElement.innerText.length < 200) {
        const main = document.querySelector('main') || document.body;
        articleElement = main;
    }

    const text = articleElement ? articleElement.innerText.trim() : '';

    // Return null if text is too short
    if (text.length < 100) {
        return null;
    }

    return {
        text: text,
        url: window.location.href,
        title: document.title
    };
}

// Highlight suspicious words on the page
function highlightWords(words, prediction) {
    if (!words || words.length === 0) return;

    const articleElement = document.querySelector('article') ||
        document.querySelector('main') ||
        document.body;

    words.forEach(([word, weight]) => {
        // Only highlight significant words
        if (Math.abs(weight) < 0.05) return;

        const className = weight > 0 ? 'newsguard-real' : 'newsguard-fake';

        // Create a regex to find the word (case insensitive)
        const regex = new RegExp(`\\b${word}\\b`, 'gi');

        // Walk through text nodes
        const walker = document.createTreeWalker(
            articleElement,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        const textNodes = [];
        while (walker.nextNode()) {
            textNodes.push(walker.currentNode);
        }

        textNodes.forEach(node => {
            if (node.nodeValue && regex.test(node.nodeValue)) {
                const span = document.createElement('span');
                span.innerHTML = node.nodeValue.replace(
                    regex,
                    `<mark class="${className}" title="Influence: ${weight.toFixed(4)}">$&</mark>`
                );
                node.parentNode.replaceChild(span, node);
            }
        });
    });
}

// Listen for messages from the popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'extract') {
        const articleData = extractArticleText();
        sendResponse(articleData);
    } else if (request.action === 'highlight') {
        analysisResult = request.result;
        if (request.result && request.result.explanation) {
            highlightWords(request.result.explanation, request.result.prediction);
        }
        sendResponse({ success: true });
    } else if (request.action === 'getResult') {
        sendResponse(analysisResult);
    }
    return true;
});
