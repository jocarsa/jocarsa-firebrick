<div class="container">
    <!-- LEFT PANE: Folder Tree & Buttons -->
    <div class="left-pane">
        <h3>Carpetas y proyectos</h3>
        <ul><?php renderFolderTree(); ?></ul>
        <?php if (isAdmin()): ?>
        <div class="buttons">
            <button onclick="openModal('create-folder-modal')">+ Nueva carpeta</button>
            <button onclick="openModal('create-project-modal')">+ New Project</button>
            <button onclick="window.location='?customers'">Clientes</button>
        </div>
        <?php endif; ?>
    </div>
    <!-- MAIN PANE -->
    <div class="main-pane">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['customers']) && isAdmin()): ?>
            <h2>Gestionar clientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th width="200">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($cust = $customersResult->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cust['username']); ?></td>
                        <td><?php echo htmlspecialchars($cust['name']); ?></td>
                        <td><?php echo htmlspecialchars($cust['email']); ?></td>
                        <td>
                            <button onclick="openModal('edit-customer-<?php echo $cust['id']; ?>')">Editar</button>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this customer?');">
                                <input type="hidden" name="delete_customer" value="1">
                                <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                <button type="submit">Borrar</button>
                            </form>
                        </td>
                    </tr>
                    <!-- EDIT CUSTOMER MODAL -->
                    <div class="modal" id="edit-customer-<?php echo $cust['id']; ?>">
                        <div class="modal-content">
                            <button class="close-btn" onclick="closeModal('edit-customer-<?php echo $cust['id']; ?>')">&times;</button>
                            <h2>Editar cliente</h2>
                            <form method="post">
                                <input type="hidden" name="update_customer" value="1">
                                <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                <label>Usuario:</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($cust['username']); ?>" required>
                                <label>Nombre:</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($cust['name']); ?>" required>
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($cust['email']); ?>" required>
                                <button type="submit">Actualizar</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
                </tbody>
            </table>

            <!-- CREATE CUSTOMER (ADMIN) -->
            <div style="margin-top: 30px;">
                <h3>Crear nuevo cliente</h3>
                <form method="post">
                    <input type="hidden" name="create_customer" value="1">
                    <label>Usuario:</label>
                    <input type="text" name="username" required>
                    <label>Contraseña:</label>
                    <input type="password" name="password" required>
                    <label>Nombre:</label>
                    <input type="text" name="name" required>
                    <label>Email:</label>
                    <input type="email" name="email" required>
                    <button type="submit">Create</button>
                </form>
            </div>
        <?php elseif ($selected_project): ?>
            <!-- SHOW SELECTED PROJECT -->
            <h2><?php echo htmlspecialchars($selected_project['title']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($selected_project['description'])); ?></p>
            <h3>Iteraciones</h3>
            <?php if ($iterations): ?>
                <ul style="list-style:none;padding:0;">
                <?php while ($iteration = $iterations->fetchArray(SQLITE3_ASSOC)): ?>
                    <li style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                        <video controls>
                            <source src="<?php echo htmlspecialchars($iteration['file_url']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div class="texto">
                            <h4><?php echo htmlspecialchars($iteration['title']); ?></h4>
                            <p class="creation-date">Created on: <?php echo htmlspecialchars($iteration['created_at']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($iteration['description'])); ?></p>
                            <?php if ($iteration['file_url']): ?>
                            <!-- Direct Download Link -->
                            <p>
                                <a href="<?php echo htmlspecialchars($iteration['file_url']); ?>"
                                   download
                                   class="botondescarga">
                                   Descargar video
                                </a>
                            </p>
                            <?php endif; ?>

                            <!-- If customer, allow comment -->
                            <?php if (isCustomer()): ?>
                            <form method="post">
                                <input type="hidden" name="comment" value="1">
                                <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                <label>Comentario:</label>
                                <textarea name="comment" required></textarea>
                                <button type="submit">Enviar</button>
                            </form>
                            <?php endif; ?>

                            <!-- Comments -->
                            <ul>
                            <?php
                            $commentQ = $db->query("SELECT * FROM comments WHERE iteration_id=" . (int)$iteration['id'] . " ORDER BY created_at ASC");
                            while ($com = $commentQ->fetchArray(SQLITE3_ASSOC)):
                            ?>
                                <li>
                                    <p><?php echo nl2br(htmlspecialchars($com['comment'])); ?></p>
                                    <small>
                                        Comentario del cliente: #<?php echo $com['customer_id']; ?>
                                        en <?php echo $com['created_at']; ?>
                                    </small>
                                </li>
                            <?php endwhile; ?>
                            </ul>

                            <!-- If Admin, can Edit or Delete iteration -->
                            <?php if (isAdmin()): ?>
                                <div style="margin-top:10px;">
                                    <button onclick="openModal('edit-iteration-<?php echo $iteration['id']; ?>')">Edit</button>
                                    <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this iteration?');">
                                        <input type="hidden" name="delete_iteration" value="1">
                                        <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                        <input type="hidden" name="parent_project_id" value="<?php echo $selected_project['id']; ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>

                                <!-- EDIT ITERATION MODAL -->
                                <div class="modal" id="edit-iteration-<?php echo $iteration['id']; ?>">
                                    <div class="modal-content">
                                        <button class="close-btn" onclick="closeModal('edit-iteration-<?php echo $iteration['id']; ?>')">&times;</button>
                                        <h2>Edit Iteration</h2>
                                        <form method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="update_iteration" value="1">
                                            <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                            <input type="hidden" name="parent_project_id" value="<?php echo $selected_project['id']; ?>">
                                            <label>Title:</label>
                                            <input type="text" name="title" value="<?php echo htmlspecialchars($iteration['title']); ?>" required>
                                            <label>Description:</label>
                                            <textarea name="description" required><?php echo htmlspecialchars($iteration['description']); ?></textarea>
                                            <label>Replace file (optional):</label>
                                            <input type="file" name="file">
                                            <button type="submit">Update Iteration</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>Todavía no hay iteraciones.</p>
            <?php endif; ?>

            <!-- Admin can create iteration -->
            <?php if (isAdmin()): ?>
                <hr>
                <h3>Crear nueva iteración</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="create_iteration" value="1">
                    <input type="hidden" name="project_id" value="<?php echo $selected_project['id']; ?>">
                    <label>Título:</label>
                    <input type="text" name="title" required>
                    <label>Descripción:</label>
                    <textarea name="description" required></textarea>
                    <label>Archivo:</label>
                    <input type="file" name="file" required>
                    <button type="submit">Crear iteración</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <!-- If no specific project or customers requested, show a default message -->
            <h2>Últimas adiciones al proyecto</h2>
            <?php
            // Fetch the last 20 iterations
            $lastIterations = $db->query("
                SELECT iterations.*, iteration_dates.created_at, projects.title AS project_title, folders.name AS folder_name
                FROM iterations
                LEFT JOIN iteration_dates ON iterations.id = iteration_dates.iteration_id
                LEFT JOIN projects ON iterations.project_id = projects.id
                LEFT JOIN folders ON projects.folder_id = folders.id
                ORDER BY iteration_dates.created_at DESC
                LIMIT 20
            ");
            ?>
            <ul style="list-style:none;padding:0;">
            <?php while ($iteration = $lastIterations->fetchArray(SQLITE3_ASSOC)): ?>
                <li style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                    <h4><?php echo htmlspecialchars($iteration['title']); ?></h4>
                    <p class="creation-date">Created on: <?php echo htmlspecialchars($iteration['created_at']); ?></p>
                    <p><strong>Project:</strong> <a href="?project_id=<?php echo $iteration['project_id']; ?>"><?php echo htmlspecialchars($iteration['project_title']); ?></a></p>
                    <p><strong>Folder:</strong> <?php echo htmlspecialchars($iteration['folder_name']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($iteration['description'])); ?></p>
                    <?php if ($iteration['file_url']): ?>
                    <!-- Direct Download Link -->
                    <p>
                        <a href="<?php echo htmlspecialchars($iteration['file_url']); ?>"
                           download
                           class="botondescarga">
                           Descargar video
                        </a>
                    </p>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

