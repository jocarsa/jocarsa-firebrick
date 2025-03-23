<div class="modal" id="create-folder-modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('create-folder-modal')">&times;</button>
            <h2>Crear carpeta</h2>
            <form method="post">
                <input type="hidden" name="create_folder" value="1">
                <label>Nombre de la carpeta:</label>
                <input type="text" name="name" required>
                <label>Carpeta superior:</label>
                <select name="parent_id">
                    <option value="">(Sin superior)</option>
                    <?php
                    // For the dropdown
                    $allFolders = $db->query("SELECT * FROM folders ORDER BY name ASC");
                    while ($fo = $allFolders->fetchArray(SQLITE3_ASSOC)) {
                        echo "<option value='{$fo['id']}'>" . htmlspecialchars($fo['name']) . "</option>";
                    }
                    ?>
                </select>
                <button type="submit">Create Folder</button>
            </form>
        </div>
    </div>

    <!-- CREATE PROJECT MODAL (ADMIN) -->
    <div class="modal" id="create-project-modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('create-project-modal')">&times;</button>
            <h2>Crear proyecto</h2>
            <form method="post">
                <input type="hidden" name="create_project" value="1">
                <label>Carpeta:</label>
                <select name="folder_id" required>
                    <option value="">-- Selecciona una carpeta --</option>
                    <?php
                    $allFolders2 = $db->query("SELECT * FROM folders ORDER BY name ASC");
                    while ($fo2 = $allFolders2->fetchArray(SQLITE3_ASSOC)) {
                        echo "<option value='{$fo2['id']}'>" . htmlspecialchars($fo2['name']) . "</option>";
                    }
                    ?>
                </select>
                <label>Título del proyecto:</label>
                <input type="text" name="title" required>
                <label>Descripción:</label>
                <textarea name="description" required></textarea>
                <button type="submit">Crear proyecto</button>
            </form>
        </div>
    </div>
