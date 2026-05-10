<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;
use PDO;

class ServiceController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/services
     * Devuelve todos los servicios activos
     */
    public function index() {
        try {
            $sql = "SELECT id, name, price, type, session_count, duration_minutes, active, created_at
                    FROM services 
                    WHERE active = 1 
                    ORDER BY name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse($services);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/services/{id}
     * Devuelve un servicio específico
     */
    public function show($id) {
        try {
            $sql = "SELECT id, name, price, type, session_count, duration_minutes, active, created_at
                    FROM services 
                    WHERE id = :id AND active = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                $this->jsonResponse(['error' => 'not_found', 'message' => 'Servicio no encontrado'], 404);
                return;
            }

            $this->jsonResponse($service);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }
}