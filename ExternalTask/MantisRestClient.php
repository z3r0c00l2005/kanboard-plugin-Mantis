<?php
namespace Kanboard\Plugin\Mantis\ExternalTask;


/**
 * Simple Mantis REST client using cURL
 */
class MantisRestClient
{
    private $baseUrl;
    private $apiToken;

    public function __construct($baseUrl, $apiToken)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = $apiToken;
    }

    private function request($method, $path, $data = null, $query = [])
    {
        $url = $this->baseUrl . '/api/rest' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);

	error_log('url: ' . $url);
	error_log('Auth:' . $this->apiToken);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        $headers = [
            'Accept: application/json',
            'Authorization: ' . $this->apiToken,
        ];

        if ($data !== null) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($json);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Mantis REST request failed: ' . $err);
        }

        $decoded = json_decode($response, true);

        if ($httpcode >= 400) {
            $msg = isset($decoded['message']) ? $decoded['message'] : $response;
            throw new \RuntimeException('Mantis REST error (HTTP ' . $httpcode . '): ' . $msg);
        }

        return $decoded;
    }

    /**
     * Get an issue by ID
     *
     * @param int $id
     * @return array
     */
    public function getIssue($id)
   {
        $res = $this->request('GET', '/issues/' . intval($id));
        // result shape: { "issues": [ {...} ], "page_size":.. }
        if (isset($res['issues']) && count($res['issues']) > 0) {
            return $res['issues'][0];
        }
        return null;
    }

    /**
     * Create an issue
     *
     * @param array $payload
     * @return array
     */
    public function createIssue(array $payload)
    {
        return $this->request('POST', '/issues', ['issue' => $payload]);
    }

    /**
     * Update an issue (partial update)
     *
     * @param int $id
     * @param array $payload
     * @return array
     */
    public function updateIssue($id, array $payload)
    {
        return $this->request('PATCH', '/issues/' . intval($id), ['issue' => $payload]);
    }

    /**
     * Add a note to an issue
     *
     * @param int $id
     * @param array $note  e.g. ['text' => '...']
     * @return array
     */
    public function addNoteToIssue($id, array $note)
    {
        return $this->request('POST', '/issues/' . intval($id) . '/notes', ['note' => $note]);
    }

    /**
     * Query issues with filters (thin wrapper)
     *
     * @param array $queryParams
     * @return array
     */
    public function getIssues(array $queryParams = [])
    {
        return $this->request('GET', '/issues', null, $queryParams);
   }
}

