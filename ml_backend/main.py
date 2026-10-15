import joblib
import os
import re
import numpy as np
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from lime.lime_text import LimeTextExplainer
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
import nltk

# Initialize FastAPI app
app = FastAPI(title="NewsGuard API", version="2.0")

# Paths anchored to the repo, not the cwd uvicorn happens to be started in
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ML_DIR = os.path.join(ROOT, "ml_backend")
DATA_DIR = os.path.join(ROOT, "ml_model", "kaggle", "input", "fake-and-real-news-dataset")

# Global variables
model = None
vectorizer = None
stop_words = None
lemmatizer = None
training_status = {
    "is_training": False,
    "progress": 0,
    "current_step": "Idle",
    "message": ""
}

# Request Models
class NewsRequest(BaseModel):
    text: str

# 1. LOAD ARTIFACTS ON STARTUP
# sync so /retrain can call it directly after training (an async def would
# return an un-awaited coroutine and never reload the model)
@app.on_event("startup")
def load_artifacts():
    global model, vectorizer, stop_words, lemmatizer
    try:
        print("Loading NewsGuard Artifacts...")
        model = joblib.load(os.path.join(ML_DIR, "newsguard_model.joblib"))
        vectorizer = joblib.load(os.path.join(ML_DIR, "newsguard_vectorizer.joblib"))
        
        # Ensure NLTK resources are available (they should be from training)
        try:
            stop_words = set(stopwords.words('english'))
            lemmatizer = WordNetLemmatizer()
        except LookupError:
            nltk.download('stopwords')
            nltk.download('wordnet')
            nltk.download('omw-1.4')
            stop_words = set(stopwords.words('english'))
            lemmatizer = WordNetLemmatizer()
            
        print("Artifacts loaded successfully!")
    except Exception as e:
        print(f"Error loading artifacts: {e}")

# 2. PREPROCESSING (Must match training)
def preprocess_text(text):
    text = str(text).lower()
    text = re.sub(r'https?://\S+|www\.\S+', '', text)
    text = re.sub(r'[^a-zA-Z\s]', '', text)
    words = text.split()
    # We need to handle the case where stop_words/lemmatizer might not be loaded if startup failed
    if stop_words and lemmatizer:
        words = [lemmatizer.lemmatize(word) for word in words if word not in stop_words]
    return " ".join(words)

# 3. PREDICTION ENDPOINT
@app.post("/predict")
async def predict_news(request: NewsRequest):
    if not model or not vectorizer:
        raise HTTPException(status_code=500, detail="Model not loaded")

    cleaned_text = preprocess_text(request.text)
    
    # Vectorize
    # transform expects an iterable, so wrap in list
    features = vectorizer.transform([cleaned_text])
    
    # Predict
    # 0 = Fake, 1 = Real (based on training script)
    prediction_class = model.predict(features)[0]
    prediction_prob = model.predict_proba(features)[0]
    
    label = "Real" if prediction_class == 1 else "Fake"
    confidence = prediction_prob[1] if prediction_class == 1 else prediction_prob[0]
    
    return {
        "status": "success",
        "prediction": label,
        "confidence_score": float(confidence),
        "raw_probability": float(prediction_prob[1]) # Prob of being Real
    }

# 4. EXPLAINABILITY ENDPOINT (LIME)
@app.post("/explain")
async def explain_news(request: NewsRequest):
    if not model or not vectorizer:
        raise HTTPException(status_code=500, detail="Model not loaded")

    # LIME Explainer
    explainer = LimeTextExplainer(class_names=['Fake', 'Real'])
    
    # Create a pipeline function for LIME
    # LIME passes raw text, we need to preprocess -> vectorize -> predict_proba
    def pipeline(texts):
        processed = [preprocess_text(t) for t in texts]
        features = vectorizer.transform(processed)
        return model.predict_proba(features)

    # Generate explanation
    # num_features=10: Top 10 words
    exp = explainer.explain_instance(request.text, pipeline, num_features=10)
    
    # Get list of (word, weight)
    explanation_list = exp.as_list()
    
    return {
        "status": "success",
        "explanation": explanation_list
    }

import shutil
import os
import sys
from fastapi import UploadFile, File, BackgroundTasks

# Add project root to path to import ml_model
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from ml_model.train_model import train

# ... (existing code) ...

# 5. RETRAINING ENDPOINT
class RetrainRequest(BaseModel):
    max_features: int = 10000
    max_iter: int = 1000

@app.post("/retrain")
async def retrain_model(request: RetrainRequest, background_tasks: BackgroundTasks):
    global training_status
    
    if training_status["is_training"]:
        raise HTTPException(status_code=400, detail="Training is already in progress")
    
    def progress_callback(step: str, progress: int, message: str = ""):
        global training_status
        training_status["current_step"] = step
        training_status["progress"] = progress
        training_status["message"] = message
        print(f"Training progress: {progress}% - {step} - {message}")
    
    def run_training():
        global model, vectorizer, training_status
        training_status["is_training"] = True
        training_status["progress"] = 0
        training_status["current_step"] = "Starting"
        
        try:
            result = train(
                data_dir=DATA_DIR,
                output_dir=ML_DIR,
                max_features=request.max_features, 
                max_iter=request.max_iter,
                progress_callback=progress_callback
            )
            training_status["progress"] = 100
            training_status["current_step"] = "Complete"
            training_status["message"] = f"Training complete. Accuracy: {result['accuracy']:.4f}"
            print(f"Training complete. Accuracy: {result['accuracy']}")
            # Reload artifacts
            load_artifacts()
        except Exception as e:
            training_status["current_step"] = "Failed"
            training_status["message"] = str(e)
            print(f"Training failed: {e}")
        finally:
            training_status["is_training"] = False

    background_tasks.add_task(run_training)
    return {"status": "success", "message": "Training started in background."}

# 6. DATA UPLOAD ENDPOINT
@app.post("/upload-data")
async def upload_data(fake_csv: UploadFile = File(None), true_csv: UploadFile = File(None)):
    data_dir = DATA_DIR
    os.makedirs(data_dir, exist_ok=True)
    
    saved_files = []
    
    if fake_csv:
        file_path = os.path.join(data_dir, "Fake.csv")
        with open(file_path, "wb") as buffer:
            shutil.copyfileobj(fake_csv.file, buffer)
        saved_files.append("Fake.csv")
        
    if true_csv:
        file_path = os.path.join(data_dir, "True.csv")
        with open(file_path, "wb") as buffer:
            shutil.copyfileobj(true_csv.file, buffer)
        saved_files.append("True.csv")
        
    if not saved_files:
        raise HTTPException(status_code=400, detail="No files uploaded")
        
    return {"status": "success", "message": f"Uploaded: {', '.join(saved_files)}"}

# 7. STATUS ENDPOINT
@app.get("/status")
def get_status():
    return {
        "model_loaded": model is not None,
        "vectorizer_loaded": vectorizer is not None,
        "model_type": "Logistic Regression + TF-IDF",
        "training_status": training_status
    }

@app.get("/")
def home():
    return {"message": "NewsGuard API is Running!"}