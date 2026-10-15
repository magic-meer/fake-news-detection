<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prediction page loads', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee('NewsGuard');
});

test('can predict fake news and saves history', function () {
    Http::fake([
        '*/predict' => Http::response([
            'status' => 'success',
            'prediction' => 'Fake',
            'confidence_score' => 0.95,
            'raw_probability' => 0.1,
            'input_preview' => 'Some fake news...',
        ], 200),
    ]);

    $response = $this->post('/predict', [
        'news_text' => 'This is some fake news text that is long enough.',
    ]);

    $response->assertStatus(200);
    $response->assertSee('FAKE NEWS');
    $response->assertSee('95.0%');

    $this->assertDatabaseHas('prediction_histories', [
        'news_text' => 'This is some fake news text that is long enough.',
        'prediction' => 'Fake',
        'confidence_score' => 0.95,
    ]);
});

test('validation works', function () {
    $response = $this->post('/predict', [
        'news_text' => 'short',
    ]);

    $response->assertSessionHasErrors('news_text');
});

test('history page loads', function () {
    $response = $this->get('/history');
    $response->assertStatus(200);
    $response->assertSee('Prediction History');
});

test('can view specific history item', function () {
    $history = \App\Models\PredictionHistory::create([
        'news_text' => 'A specific history item text.',
        'prediction' => 'Real',
        'confidence_score' => 0.88,
    ]);

    $response = $this->get("/history/{$history->id}");
    $response->assertStatus(200);
    $response->assertSee('A specific history item text.');
    $response->assertSee('88.0%');
});

test('can predict from url', function () {
    Http::fake([
        'example.com/*' => Http::response('<html><body><article>This is a scraped article text that needs to be longer than fifty characters to pass the validation check in the scraper service. So I am adding more text here.</article></body></html>', 200),
        '*/predict' => Http::response([
            'status' => 'success',
            'prediction' => 'Real',
            'confidence_score' => 0.99,
            'raw_probability' => 0.99,
            'input_preview' => 'This is a scraped article text...',
        ], 200),
        '*/explain' => Http::response([
            'status' => 'success',
            'explanation' => [['scraped', 0.1], ['article', 0.2]],
        ], 200),
    ]);

    $response = $this->post('/predict', [
        'news_url' => 'http://example.com/article',
    ]);

    $response->assertStatus(200);
    $response->assertSee('REAL NEWS');
    $response->assertSee('Why this prediction?');
    $response->assertSee('scraped');
    
    $this->assertDatabaseHas('prediction_histories', [
        'news_text' => 'This is a scraped article text that needs to be longer than fifty characters to pass the validation check in the scraper service. So I am adding more text here.',
        'explanation' => json_encode([['scraped', 0.1], ['article', 0.2]]),
    ]);
});

test('validation requires text or url', function () {
    $response = $this->post('/predict', []);
    $response->assertSessionHasErrors(['news_text', 'news_url']);
});
test('settings page loads', function () {
    Http::fake([
        '*/status' => Http::response(['model_loaded' => true], 200),
    ]);

    $response = $this->get('/settings');
    $response->assertStatus(200);
    $response->assertSee('Model Settings');
    $response->assertSee('Model Active');
});

test('can upload datasets', function () {
    Http::fake([
        '*/upload-data' => Http::response(['status' => 'success'], 200),
    ]);

    $fakeCsv = \Illuminate\Http\UploadedFile::fake()->create('Fake.csv', 100);
    $trueCsv = \Illuminate\Http\UploadedFile::fake()->create('True.csv', 100);

    $response = $this->post('/settings/upload', [
        'fake_csv' => $fakeCsv,
        'true_csv' => $trueCsv,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Datasets uploaded successfully!');
});

test('can trigger retraining', function () {
    Http::fake([
        '*/retrain' => Http::response(['status' => 'success'], 200),
    ]);

    $response = $this->post('/settings/retrain', [
        'max_features' => 5000,
        'max_iter' => 500,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Training started in background! Check status later.');
});
