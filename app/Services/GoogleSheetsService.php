<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

//service for google sheets API
class GoogleSheetsService
{
    private Sheets $service;
    private string $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SHEET_ID');

        $this->client = new Client();
        $this->client->setAuthConfig(env('GOOGLE_SHEETS_CREDENTIALS_PATH'));
        $this->client->addScope(Sheets::SPREADSHEETS);

        $this->service = new Sheets($this->client);
    }

    public function getRows(): array
    {
        $range = 'Sheet1!A:F';
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues() ?? [];
    }

    public function updateRow(int $id, array $data)
    {
        $rows = $this->getRows();
        foreach ($rows as $index => $row) {
            if ((int) $row[0] === $id) {
                $range = "Sheet1!A" . ($index + 1) . ":F" . ($index + 1);
                $body = new Sheets\ValueRange(['values' => [$data]]);
                $params = ['valueInputOption' => 'RAW'];
                $this->service->spreadsheets_values->update(
                    $this->spreadsheetId,
                    $range,
                    $body,
                    $params
                );
                return;
            }
        }
    }

    public function addRow(array $data)
    {
        $range = 'Sheet1';
        $body = new Sheets\ValueRange(['values' => [$data]]);
        $params = ['valueInputOption' => 'RAW'];
        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }
}
