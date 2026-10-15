# NewsGuard

<div align="center">

<img src="browser-extension/icons/icon128.png" width="96" alt="NewsGuard">

**A full-stack fake news detection system with explainable AI, web dashboard, and browser extension**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![FastAPI](https://img.shields.io/badge/FastAPI-Python-009688?logo=fastapi)](https://fastapi.tiangolo.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4-38B2AC?logo=tailwind-css)](https://tailwindcss.com)
[![Chrome Extension](https://img.shields.io/badge/Chrome-Extension-4285F4?logo=google-chrome)](https://developer.chrome.com/docs/extensions/)

</div>

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Branding](#branding)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Usage](#usage)
- [Browser Extension](#browser-extension)
- [Screenshots](#screenshots)
- [Model Details](#model-details)
- [API Documentation](#api-documentation)
- [Contributing](#contributing)

---

## Overview

**NewsGuard** is a comprehensive fake news detection system that combines machine learning, web development, and browser automation to help users identify misinformation online. The system provides:

- **Web Dashboard**: Analyze news articles manually with detailed explanations
- **ML API**: FastAPI backend serving a Logistic Regression + TF-IDF model
- **Browser Extension**: Automatic detection and inline highlighting on any news website
- **Explainability**: LIME-based explanations showing which words influenced the prediction

---

## Features

### Detection Capabilities
- Text-based fake news classification (Real vs Fake)
- URL-based analysis with automatic content extraction
- Confidence score for each prediction
- Explainability via LIME (Local Interpretable Model-agnostic Explanations)

### User Interface
- Modern, responsive web dashboard (Light/Dark themes)
- Real-time loading indicators and progress tracking
- Prediction history with detailed logs
- Admin settings for dataset upload and model retraining

### Browser Extension
- Chrome & Firefox support (Manifest v3)
- Automatic article text extraction
- Inline word highlighting (green = real, red = fake)
- Popup with instant results and web app integration

### Model Management
- Upload custom training datasets (CSV)
- Retrain model with configurable parameters
- Real-time training progress tracking
- Model status monitoring

---

## Branding

The extension icon is the single source of truth for the whole project. Everything else
follows from it.

| Token | Value | Where |
|---|---|---|
| Primary | `#2563EB` | popup buttons, spinner, every `blue-*` Tailwind class |
| Primary hover | `#1D4ED8` | button hover state |
| Mark | `browser-extension/icons/icon128.png` | blue circle with a white tick |
| Name | NewsGuard | popup, dashboard header, README |

Rules:

- `#2563EB` is exactly Tailwind's `blue-600`, so the dashboard uses plain `blue-*`
  utilities and the popup uses the literal hex. No second shade of brand blue.
- The tick appears as the mark, not as text. Do not substitute an emoji for it.
- Semantic colours are separate and stay separate: `REAL` is green, `FAKE` is red.

Dashboard logo is the icon redrawn as inline SVG (`layouts/app.blade.php`) so it stays crisp
and inherits `currentColor`; the popup uses the PNG directly.

---

## Architecture

```
┌─────────────────┐      ┌──────────────────┐      ┌─────────────────┐
│  Browser Ext    │◄────►│  Laravel Web App │◄────►│  FastAPI ML API │
│  (Chrome/FF)    │      │  (Dashboard)     │      │  (TF-IDF + LR)  │
└─────────────────┘      └──────────────────┘      └─────────────────┘
                                  │
                                  ▼
                         ┌────────────────┐
                         │   MySQL/SQLite │
                         │   (History DB) │
                         └────────────────┘
```

### Data Flow
1. User inputs text/URL or uses browser extension
2. Laravel receives request and forwards to ML API
3. FastAPI processes text, runs prediction & LIME explanation
4. Results return to Laravel, saved in database
5. Laravel displays results with visualizations
6. Browser extension highlights suspicious words inline

---

## Tech Stack

### Frontend
- **Framework**: Laravel Blade Templates
- **Styling**: TailwindCSS + Custom CSS
- **Interactivity**: Alpine.js
- **Browser Extension**: Manifest v3 (Chrome/Firefox)

### Backend
- **Web Framework**: Laravel 12 (PHP 8.2+)
- **ML API**: FastAPI (Python)
- **Database**: MySQL / SQLite
- **Queue System**: Laravel Queues (optional)

### Machine Learning
- **Model**: Logistic Regression
- **Vectorizer**: TF-IDF (unigrams + bigrams)
- **Explainability**: LIME (Local Interpretable Model-agnostic Explanations)
- **Libraries**: scikit-learn, joblib, NLTK

### DevOps
- **Package Management**: Composer (PHP), pip (Python), npm (JS)
- **Development**: Laravel Sail (Docker), Artisan, Uvicorn

---

## Installation

### Prerequisites

Docker. Everything else — PHP 8.4, Composer, Python 3.13, scikit-learn — lives inside the
images defined in `docker/`.

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/fake-news-detection.git
cd fake-news-detection
```

### 2. Build & Start
```bash
docker compose build      # first run only
docker compose up -d
docker compose logs -f ml # wait for "Application startup complete"
```

| Service | URL |
|---|---|
| Laravel dashboard | http://localhost:8000 |
| FastAPI ML API | http://localhost:8001 |

The ML API runs on **8001**, not 8000 — port 8000 belongs to Laravel because the browser
extension posts to it. `ML_API_URL` in `fake-news-detection/.env` is set to
`http://ml:8001` (the compose service name).

> Do not move that value into `docker-compose.yml`'s `environment:` block:
> `php artisan serve` only forwards `APP_ENV`, `PATH` and a dozen other variables to its
> `php -S` worker (`ServeCommand::$passthroughVariables`), so the app never sees them and
> silently falls back to `.env`.

### 3. First-Time App Setup
Already done if you cloned this working tree. On a fresh clone:
```bash
docker compose run --rm web sh -c \
  "cp .env.example .env && php artisan key:generate && \
   touch database/database.sqlite && php artisan migrate --force"
```

### 4. Run the Tests
```bash
docker compose run --rm web php artisan test
```

### Optional: Browser Extension
`chrome://extensions/` → enable Developer mode → **Load unpacked** → select
`browser-extension/`. No build step; the extension talks to the Laravel app on port 8000.

### Running Without Docker

Requires PHP 8.4+ (Symfony 8 needs it), Composer, and Python 3.13.

```bash
# Laravel
cd fake-news-detection
composer install
cp .env.example .env && echo 'ML_API_URL=http://127.0.0.1:8001' >> .env
php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan serve                                   # :8000

# FastAPI ML Backend
cd ..
python3.13 -m venv .venv && .venv/bin/pip install -r requirements.txt
cd ml_backend && ../.venv/bin/uvicorn main:app --reload --port 8001
```

The trained artifacts are committed (`newsguard_model.joblib`, `newsguard_vectorizer.joblib`)
and were pickled with **scikit-learn 1.7.2**, which `requirements.txt` pins — a newer
scikit-learn will fail to load them.

Frontend assets: the routed views load Tailwind from a CDN, so no build is required. Only
the unrouted `welcome.blade.php` uses `@vite`; build it with `pnpm install && pnpm run build`.

---

## Usage

### Web Dashboard
1. Navigate to `http://localhost:8000`
2. Paste article text or enter a URL
3. Click "Analyze Authenticity"
4. View prediction, confidence, and explanation

### Settings Page
1. Go to `http://localhost:8000/settings`
2. **Upload Dataset**: Upload `Fake.csv` and `True.csv` files
3. **Retrain Model**: Configure parameters and click "Start Retraining"
4. Monitor training progress in real-time

### History
1. Go to `http://localhost:8000/history`
2. View all past predictions
3. Click any item to see detailed explanation

---

## Browser Extension

### Installation
1. Open `chrome://extensions/` (Chrome) or `about:debugging#/runtime/this-firefox` (Firefox)
2. Enable "Developer mode"
3. Click "Load unpacked" (Chrome) or "Load Temporary Add-on" (Firefox)
4. Select the `browser-extension` folder

### Usage
1. Navigate to any news article
2. Click the NewsGuard extension icon
3. Click "Analyze This Article"
4. View results in popup
5. Click "Highlight on Page" to see inline word highlights
6. Click "View Explanation" to open detailed report in web app

See [`browser-extension/README.md`](browser-extension/README.md) for more details.

---

## Screenshots

![webapp_home_screenshot](./screenshots/image.png)

---

## Model Details

### Training Data
- **Dataset**: Kaggle "Fake and real news dataset"
- **Classes**: Fake (0), Real (1)
- **Features**: TF-IDF vectors (10,000 features, unigrams + bigrams)

### Model Architecture
- **Algorithm**: Logistic Regression
- **Max Iterations**: 1,000 (configurable)
- **Preprocessing**: Lowercase, punctuation removal, stopword removal, lemmatization

### Explainability
- **Method**: LIME (Local Interpretable Model-agnostic Explanations)
- **Features**: Top 10 contributing words with weights
- **Visualization**: Word-level highlighting with positive/negative influence

### Performance
- **Accuracy**: ~95% (on test set)
- **Evaluation**: Classification report, confusion matrix

---

## API Documentation

### ML API Endpoints

#### `POST /predict`
Predict if text is fake or real.

**Request:**
```json
{
  "text": "Article content here..."
}
```

**Response:**
```json
{
  "status": "success",
  "prediction": "Real",
  "confidence_score": 0.92,
  "raw_probability": 0.92
}
```

#### `POST /explain`
Get LIME explanation for text.

**Request:**
```json
{
  "text": "Article content here..."
}
```

**Response:**
```json
{
  "status": "success",
  "explanation": [
    ["word1", 0.45],
    ["word2", -0.32]
  ]
}
```

#### `GET /status`
Get model and training status.

**Response:**
```json
{
  "model_loaded": true,
  "vectorizer_loaded": true,
  "model_type": "Logistic Regression + TF-IDF",
  "training_status": {
    "is_training": false,
    "progress": 0,
    "current_step": "Idle"
  }
}
```

### Laravel API Endpoints

#### `POST /api/extension/check`
Extension endpoint for article analysis.

**Request:**
```json
{
  "text": "Article content...",
  "url": "https://example.com/article",
  "title": "Article Title"
}
```

**Response:**
```json
{
  "success": true,
  "prediction": "Real",
  "confidence_score": 0.92,
  "explanation": [...],
  "history_id": 123
}
```

---

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Author

**Meher Ali**
- GitHub: [@meheralimeer](https://github.com/meheralimeer)
- Email: meherali.meer@gmail.com

---

## Acknowledgments

- [Kaggle Fake and real news dataset](https://www.kaggle.com/datasets/clmentbisaillon/fake-and-real-news-dataset)
- [LIME - Local Interpretable Model-agnostic Explanations](https://github.com/marcotcr/lime)
- Laravel, FastAPI, and TailwindCSS communities

---

<div align="center">
Made with using Laravel, FastAPI, and TailwindCSS
</div>
