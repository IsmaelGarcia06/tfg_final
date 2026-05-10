<?php

namespace Src\Controllers;

class IntegrationController extends BaseController {

    public function connectGoogle() {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '';
        if (empty($clientId)) {
            die("Error: GOOGLE_CLIENT_ID no configurado");
        }

        $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/google/callback';

        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'https://www.googleapis.com/auth/calendar',
                'access_type' => 'offline',
                'prompt' => 'consent'
            ]);

        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback() {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            die("Error de autenticación: No authorization code received");
        }

        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '';
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '';
        $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/google/callback';

        if (empty($clientId) || empty($clientSecret)) {
            die("Error: Credenciales de Google no configuradas");
        }

        try {
            $client = new \Google\Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (!$token) {
                die("Error: No se recibió token");
            }

            // Obtener información del usuario de Google
            $client->setAccessToken($token);
            $service = new \Google\Service\Calendar($client);
            $calendarList = $service->calendarList->listCalendarList();
            $primaryCalendar = null;
            foreach ($calendarList->getItems() as $calendar) {
                if ($calendar->getPrimary()) {
                    $primaryCalendar = $calendar->getId();
                    break;
                }
            }

            // Guardar en BD
            $stmt = $this->db->prepare("UPDATE users SET google_access_token = :at, google_refresh_token = :rt, google_token_expires_at = :exp, google_calendar_id = :cid WHERE id = :id");
            $stmt->execute([
                'at' => json_encode($token),
                'rt' => $token['refresh_token'] ?? null,
                'exp' => date('Y-m-d H:i:s', time() + ($token['expires_in'] ?? 3600)),
                'cid' => $primaryCalendar ?? 'primary',
                'id' => $this->userId
            ]);

            error_log("Google Auth: Usuario $this->userId autenticado correctamente");
            header('Location: ' . BASE_URL . '/dashboard?msg=google_connected');
            exit;
        } catch (\Exception $e) {
            error_log("Google Auth Error: " . $e->getMessage());
            die("Error de autenticación: " . $e->getMessage());
        }
    }

    /**
     * Desconectar Google Calendar
     */
    public function disconnectGoogle() {
        try {
            // Limpiar tokens de Google de la BD
            $stmt = $this->db->prepare("UPDATE users SET google_access_token = NULL, google_refresh_token = NULL, google_token_expires_at = NULL WHERE id = :id");
            $stmt->execute(['id' => $this->userId]);

            error_log("Google Auth: Usuario $this->userId desconectado de Google Calendar");
            header('Location: ' . BASE_URL . '/config?msg=google_disconnected');
            exit;
        } catch (\Exception $e) {
            error_log("Google Disconnect Error: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/config?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}