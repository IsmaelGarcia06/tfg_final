<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Profesionales - CRM Clínica</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f6f9; }
        header { background-color: #fff; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: bold; font-size: 1.2rem; color: #333; }
        .user-menu a { margin-left: 1rem; text-decoration: none; color: #007bff; }
        main { padding: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #dee2e6; }
        th { background-color: #f8f9fa; }
        input[type="number"] { width: 80px; padding: 0.25rem; }
        button.save-btn { background-color: #28a745; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; }
        button.save-btn:hover { background-color: #218838; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; display: none; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <header>
        <div class="logo">CRM Clínica</div>
        <div class="user-menu">
            <a href="/dashboard">Volver al Dashboard</a>
        </div>
    </header>
    <main>
        <div id="alert-box" class="alert"></div>

        <div class="card">
            <h1>Gestión de Profesionales</h1>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Comisión (%)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($professionals as $prof): ?>
                    <tr>
                        <td><?= htmlspecialchars($prof['name']) ?></td>
                        <td><?= htmlspecialchars($prof['email']) ?></td>
                        <td>
                            <input type="number" id="commission-<?= $prof['id'] ?>" 
                                   value="<?= htmlspecialchars($prof['commission_percentage']) ?>" 
                                   min="0" max="100" step="0.01">
                        </td>
                        <td>
                            <button class="save-btn" onclick="updateCommission(<?= $prof['id'] ?>)">Guardar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        async function updateCommission(id) {
            const input = document.getElementById('commission-' + id);
            const percentage = input.value;
            const alertBox = document.getElementById('alert-box');

            try {
                const response = await fetch(`/api/professionals/${id}/commission`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ commission_percentage: percentage })
                });

                const data = await response.json();

                if (response.ok) {
                    alertBox.className = 'alert alert-success';
                    alertBox.textContent = 'Comisión actualizada correctamente.';
                    alertBox.style.display = 'block';
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.textContent = data.message || 'Error al actualizar.';
                    alertBox.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                alertBox.className = 'alert alert-error';
                alertBox.textContent = 'Error de conexión.';
                alertBox.style.display = 'block';
            }

            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
