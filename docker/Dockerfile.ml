FROM python:3.13-slim

WORKDIR /app

ENV PYTHONUNBUFFERED=1 \
    NLTK_DATA=/app/.nltk_data

# scikit-learn is pinned to 1.7.2 in requirements.txt: the joblib artifacts in
# ml_backend were pickled with it and have no newer cp313 wheels to fall back on.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt \
 && python -c "import nltk, os; d = os.environ['NLTK_DATA']; [nltk.download(p, download_dir=d, quiet=True) for p in ('stopwords','wordnet','omw-1.4')]"

COPY ml_backend/main.py \
     ml_backend/newsguard_model.joblib \
     ml_backend/newsguard_vectorizer.joblib \
     ml_backend/
COPY ml_model/train_model.py ml_model/

EXPOSE 8001

# --app-dir . puts /app on sys.path so `ml_backend.main` and `ml_model` import
CMD ["uvicorn", "ml_backend.main:app", "--app-dir", ".", "--host", "0.0.0.0", "--port", "8001", "--reload"]