<?php

class TrademarkSearch
{
    private string $accessToken;
    const CLIENT_ID = '7hkQFmlHi8ezty9Xlw2woHx0qa052UPt';
    const CLIENT_SECRET = 'xqQFfK0KrnGYipk58aTfWkbyONejbuLTIeBt8XYziMU8a-JvJLGo1rx9TfZRG1Qo';

    const URL = 'https://production.api.ipaustralia.gov.au/public';

    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
    }

    public function displayResults(string $searchText): void
    {
        $searchResult = $this->searchTrademarks($searchText);

        $totalResults = $searchResult['count'] ?? 0;
        echo "Results: " . $totalResults . PHP_EOL;

        if ($totalResults > 0) {
            $trademarkIds = $searchResult['trademarkIds'];
            $counter = 1;

            foreach ($trademarkIds as $trademarkId) {
                echo $counter . ". ";
                $decodedData = $this->getTrademarkDetails($trademarkId);

                $fields = [
                    'number' => $decodedData['number'] ?? 'no details',
                    'url_logo' => !empty($decodedData['images']['images']) ? implode(", ", $decodedData['images']['images']) : 'no details',
                    'name' => implode(', ', $decodedData['words'] ?? []) ?? 'no details',
                    'class' => $decodedData['goodsAndServices'][0]['class'] ?? 'no details',
                    'status' => $decodedData['statusGroup'] ?? 'no details',
                    'url_details_page' => "https://search.ipaustralia.gov.au/trademarks/search/view/{$decodedData['number']}",
                ];

                foreach ($fields as $key => $value) {
                    echo '"' . $key . '": "' . $value . "\",\n";
                }

                $counter++;
            }
        } else {
            echo "No results found." . PHP_EOL;
        }
    }

    private function getTrademarkDetails(string $trademarkId): array
    {
        $url = self::URL . "/australian-trade-mark-search-api/v1/trade-mark/$trademarkId";
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $data = $this->functionCurl($url, $header);
        return json_decode($data, true);
    }

    private function functionCurl(string $url, array $header, string $postData = ''): string
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($postData != '') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    private function getAccessToken(): string
    {
        $tokenUrl = self::URL . '/external-token-api/v1/access_token';

        $tokenData = [
            'grant_type' => 'client_credentials',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ];

        $header = ['Content-Type: application/x-www-form-urlencoded'];

        $tokenDataString = http_build_query($tokenData);
        $tokenResponse = $this->functionCurl($tokenUrl, $header, $tokenDataString);

        $tokenData = json_decode($tokenResponse, true);

        if (isset($tokenData['access_token'])) {
            return $tokenData['access_token'];
        } else {
            echo 'Token error: ' . $tokenResponse;
            exit;
        }
    }

    private function searchTrademarks(string $searchText): array
    {
        $searchData = [
            'changedSinceDate' => '',
            'pageNumber' => 0,
            'pageSize' => 0,
            'rows' => [
                [
                    'op' => 'AND',
                    'query' => [
                        'word' => [
                            'text' => $searchText,
                            'type' => 'PART',
                        ],
                        'wordPhrase' => '',
                    ],
                ],
            ],
            'sort' => [
                'field' => 'NUMBER',
                'direction' => 'ASCENDING',
            ],
        ];

        $searchUrl = self::URL . '/australian-trade-mark-search-api/v1/search/advanced';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $searchResponse = $this->functionCurl($searchUrl, $headers, json_encode($searchData));

        return json_decode($searchResponse, true);
    }
}

if ($argc != 2) {
    echo "Error! Enter only one word \n";
    exit(1);
}
$searchText = $argv[1];

$trademarkSearch = new TrademarkSearch();
$trademarkSearch->displayResults($searchText);