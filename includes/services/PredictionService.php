<?php
// --- AI Prediction Service ---

// Include the simulated library files
require_once __DIR__ . '/../lib/php-ml/Regression/Regressor.php';
require_once __DIR__ . '/../lib/php-ml/Regression/RandomForest.php';
require_once __DIR__ . '/../lib/php-ml/Dataset/Dataset.php';
require_once __DIR__ . '/../lib/php-ml/Dataset/ArrayDataset.php';

use Phpml\Regression\RandomForest;
use Phpml\Dataset\ArrayDataset;

class PredictionService
{
    private $modelStoragePath;

    public function __construct()
    {
        // Define where to save the trained models
        $this->modelStoragePath = __DIR__ . '/../../uploads/models/';
        if (!is_dir($this->modelStoragePath)) {
            mkdir($this->modelStoragePath, 0777, true);
        }
    }

    /**
     * Trains a model for a given patient and saves it.
     *
     * @param int $patientId
     * @param array $records Blood sugar records for the patient
     * @return bool True on success, false on failure
     */
    public function trainAndSaveModel(int $patientId, array $records): bool
    {
        if (count($records) < 10) {
            // Not enough data to train a meaningful model
            return false;
        }

        // --- Feature Engineering ---
        // We'll use time-based features to predict the blood sugar value.
        // Features: hour of the day (0-23), day of the week (0-6)
        $samples = [];
        $targets = [];
        foreach ($records as $record) {
            $timestamp = new \DateTime($record['recorded_at']);
            $samples[] = [
                (int)$timestamp->format('H'), // Hour
                (int)$timestamp->format('w'), // Day of week
            ];
            $targets[] = (float)$record['value1'];
        }

        // Create a dataset
        $dataset = new ArrayDataset($samples, $targets);

        // Train the model
        $regressor = new RandomForest();
        $regressor->train($dataset->getSamples(), $dataset->getTargets());

        // Save the trained model to a file
        $modelFileName = $this->modelStoragePath . 'patient_' . $patientId . '.model';
        $serializedModel = serialize($regressor);
        if (file_put_contents($modelFileName, $serializedModel)) {
            return true;
        }

        return false;
    }

    /**
     * Generates a 7-day forecast for a given patient.
     *
     * @param int $patientId
     * @return array|null The forecast data or null if model doesn't exist
     */
    public function get7DayForecast(int $patientId): ?array
    {
        $modelFileName = $this->modelStoragePath . 'patient_' . $patientId . '.model';

        if (!file_exists($modelFileName)) {
            return null; // Model needs to be trained first
        }

        $serializedModel = file_get_contents($modelFileName);
        $regressor = unserialize($serializedModel);

        // --- Generate future feature sets ---
        // Let's predict for every 6 hours over the next 7 days.
        $forecast = [];
        $now = new \DateTime();

        for ($i = 0; $i < (7 * 4); $i++) { // 4 predictions per day
            $futureTimestamp = clone $now;
            $futureTimestamp->modify('+' . ($i * 6) . ' hours');

            $futureSample = [
                (int)$futureTimestamp->format('H'),
                (int)$futureTimestamp->format('w'),
            ];

            $predictedValue = $regressor->predict($futureSample);

            $forecast[] = [
                'timestamp' => $futureTimestamp->format('Y-m-d H:i:s'),
                'predicted_value' => round($predictedValue, 2),
            ];
        }

        return $forecast;
    }
}
