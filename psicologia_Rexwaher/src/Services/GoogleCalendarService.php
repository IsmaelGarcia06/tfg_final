<?php

namespace Src\Services;

use PDO;

class GoogleCalendarService {
    private $db;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct() {
        $this->db = \getDBConnection();
        $this->clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '';
        $this->clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '';
        $this->redirectUri = 'http://localhost:8000/auth/google/callback';
    }

    /**
     * Obtiene el cliente de Google configurado y autenticado para un usuario
     */
    private function getClient($userId) {
        // 1. Obtener tokens del usuario
        $stmt = $this->db->prepare("SELECT google_refresh_token, google_access_token, google_token_expires_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !$user['google_refresh_token']) {
            return null; // Usuario no conectado
        }

        try {
            $client = new \Google\Client();
            $client->setClientId($this->clientId);
            $client->setClientSecret($this->clientSecret);

            // Parsear el token guardado (puede ser JSON o array)
            $accessToken = $user['google_access_token'];
            if (is_string($accessToken)) {
                $accessToken = json_decode($accessToken, true);
            }

            if (!is_array($accessToken)) {
                error_log("Google Client: Token inválido para usuario $userId");
                return null;
            }

            $client->setAccessToken($accessToken);

            // Si el token expiró, refrescarlo
            if ($client->isAccessTokenExpired()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($user['google_refresh_token']);
                if ($newToken) {
                    $this->updateAccessToken($userId, $newToken);
                    $client->setAccessToken($newToken);
                } else {
                    error_log("Google Client: No se pudo refrescar token para usuario $userId");
                    return null;
                }
            }

            return $client;
        } catch (\Exception $e) {
            error_log("Google Client Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un evento en Google Calendar
     */
    public function createEvent($userId, $sessionData) {
        $client = $this->getClient($userId);
        if (!$client) {
            error_log("Google Calendar: No client para usuario $userId");
            return null;
        }

        // Mapeo de datos
        $eventData = new \Google\Service\Calendar\Event([
            'summary' => 'Sesión con ' . $sessionData['patient_name'],
            'description' => $sessionData['notes'] ?? '',
            'start' => new \Google\Service\Calendar\EventDateTime([
                'dateTime' => str_replace(' ', 'T', $sessionData['start_time']),
                'timeZone' => 'Europe/Madrid'
            ]),
            'end' => new \Google\Service\Calendar\EventDateTime([
                'dateTime' => str_replace(' ', 'T', $sessionData['end_time']),
                'timeZone' => 'Europe/Madrid'
            ])
        ]);

        try {
            $service = new \Google\Service\Calendar($client);
            $event = $service->events->insert('primary', $eventData);
            error_log("Google Calendar: Evento creado exitosamente. ID: " . $event->id . " Usuario: $userId");
            return $event->id;
        } catch (\Exception $e) {
            error_log("Google Sync Error: " . $e->getMessage() . " Usuario: $userId");
            return null;
        }
    }

    /**
     * Actualiza un evento existente
     */
    public function updateEvent($userId, $googleEventId, $sessionData) {
        if (!$googleEventId) return;

        $client = $this->getClient($userId);
        if (!$client) {
            error_log("Google Calendar: No client para actualizar evento $googleEventId");
            return;
        }

        try {
            $service = new \Google\Service\Calendar($client);
            $event = $service->events->get('primary', $googleEventId);
            $event->setSummary('Sesión con ' . $sessionData['patient_name']);
            $event->setDescription($sessionData['notes'] ?? '');
            $event->setStart(new \Google\Service\Calendar\EventDateTime([
                'dateTime' => str_replace(' ', 'T', $sessionData['start_time']),
                'timeZone' => 'Europe/Madrid'
            ]));
            $event->setEnd(new \Google\Service\Calendar\EventDateTime([
                'dateTime' => str_replace(' ', 'T', $sessionData['end_time']),
                'timeZone' => 'Europe/Madrid'
            ]));
            $service->events->update('primary', $googleEventId, $event);
            error_log("Google Calendar: Evento actualizado. ID: $googleEventId Usuario: $userId");
        } catch (\Exception $e) {
            error_log("Google Sync Update Error: " . $e->getMessage() . " ID: $googleEventId Usuario: $userId");
        }
    }

    /**
     * Elimina un evento
     */
    public function deleteEvent($userId, $googleEventId) {
        if (!$googleEventId) return;

        $client = $this->getClient($userId);
        if (!$client) {
            error_log("Google Calendar: No client para eliminar evento $googleEventId");
            return;
        }

        try {
            $service = new \Google\Service\Calendar($client);
            $service->events->delete('primary', $googleEventId);
            error_log("Google Calendar: Evento eliminado. ID: $googleEventId Usuario: $userId");
        } catch (\Exception $e) {
            error_log("Google Sync Delete Error: " . $e->getMessage() . " ID: $googleEventId Usuario: $userId");
        }
    }

    private function updateAccessToken($userId, $tokenData) {
        try {
            $sql = "UPDATE users SET google_access_token = :at, google_token_expires_at = :exp WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'at' => json_encode($tokenData),
                'exp' => date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600)),
                'id' => $userId
            ]);
            error_log("Google Calendar: Access token actualizado para usuario $userId");
        } catch (\Exception $e) {
            error_log("Google Token Update Error: " . $e->getMessage());
        }
    }
}