# jocarsa-firebrick

# Manual de Usuario 
**Sistema de gestión de carpetas, proyectos e iteraciones con soporte de comentarios y usuarios (admins y clientes).**

Este documento describe cómo utilizar el software basado en el archivo PHP compartido. El sistema permite a un administrador crear carpetas, proyectos e iteraciones (videos), así como crear y administrar cuentas de clientes. Los clientes pueden iniciar sesión para ver las iteraciones y dejar comentarios.

---

## Tabla de Contenido

1. [Introducción](#introducción)  
2. [Requerimientos](#requerimientos)  
3. [Instalación y Configuración](#instalación-y-configuración)  
4. [Estructura de Datos (Tablas)](#estructura-de-datos-tablas)  
5. [Inicio de Sesión y Registro](#inicio-de-sesión-y-registro)  
6. [Interfaz de Usuario](#interfaz-de-usuario)  
   1. [Panel Lateral (Carpetas y Proyectos)](#panel-lateral-carpetas-y-proyectos)  
   2. [Panel Principal (Contenido)](#panel-principal-contenido)  
7. [Operaciones para Administradores](#operaciones-para-administradores)  
   1. [Crear Carpeta](#crear-carpeta)  
   2. [Crear Proyecto](#crear-proyecto)  
   3. [Crear Iteración](#crear-iteración)  
   4. [Gestión de Clientes](#gestión-de-clientes)  
8. [Operaciones para Clientes](#operaciones-para-clientes)  
   1. [Visualizar Iteraciones](#visualizar-iteraciones)  
   2. [Descargar Archivo (Video)](#descargar-archivo-video)  
   3. [Comentar](#comentar)  
9. [Cerrar Sesión (Logout)](#cerrar-sesión-logout)  
10. [Seguridad y Recomendaciones](#seguridad-y-recomendaciones)  
11. [Contacto y Soporte](#contacto-y-soporte)  

---

## Introducción
Este software es una plataforma que organiza proyectos en diferentes carpetas, permitiendo mantener iteraciones o versiones de videos asociados a cada proyecto. Está pensado para que:

- **Administradores** (rol `admin`)  
  - Creen y administren carpetas, proyectos e iteraciones.  
  - Registren y administren clientes.
  - Cuenten con permisos de edición y borrado en todo el contenido (carpetas, proyectos, iteraciones, clientes).

- **Clientes** (rol `customer`)  
  - Puedan acceder a los proyectos y sus iteraciones para visualizar los videos y dejar comentarios.  
  - No tienen permisos de administración (creación, edición o borrado de carpetas/proyectos).

---

## Requerimientos
1. **Servidor web** con soporte **PHP** (versión 7.4+ recomendada).
2. **Extensión SQLite** habilitada en PHP (para la base de datos).
3. Permisos de escritura en la carpeta donde se almacenan los archivos subidos (`uploads/`).

---

## Instalación y Configuración

1. **Ubicar el archivo PHP**  
   Copia el archivo principal (por ejemplo `index.php`) en el directorio raíz o en una carpeta de tu servidor web.

2. **Configurar el entorno**  
   - Asegúrate de que el servidor web tenga permisos de lectura y ejecución sobre el archivo `index.php`.
   - Asegúrate de que el directorio `uploads/` (se crea automáticamente si no existe) tenga permisos de escritura para poder guardar los videos.

3. **Primer arranque**  
   - Al cargar la página por primera vez (p. ej. `https://tusitio.com/index.php`), el sistema creará automáticamente la base de datos SQLite `projects.db` y las tablas necesarias si no existen.
   - No se requiere ninguna acción adicional para inicializar la base de datos.

---

## Estructura de Datos (Tablas)
El software crea las siguientes tablas en `projects.db`:

- **users**  
  Guarda la información de los administradores (username, password, role).

- **folders**  
  Guarda las carpetas (pueden tener jerarquía a través de `parent_id`).

- **projects**  
  Almacena los proyectos, cada uno asignado a una carpeta (`folder_id`).

- **iterations**  
  Guarda las iteraciones (videos) con título, descripción y URL de archivo (`file_url`).

- **customers**  
  Guarda la información de los clientes (username, password, nombre, email).

- **comments**  
  Guarda los comentarios que hacen los clientes sobre cada iteración.

---

## Inicio de Sesión y Registro

### Página de Login
Cuando accedes a la URL principal (por ejemplo, `index.php`) y **no hay sesión iniciada**, verás un formulario de **Login** y un enlace opcional para el **Registro** de un administrador.

1. **Iniciar Sesión**  
   - Ingresa tus credenciales: **username** y **password**.  
   - Pulsa en **Login**.  
   - Si los datos son correctos, el sistema te redirige al **dashboard**.  
   - Si son incorrectos, mostrará un mensaje de error.

2. **Registro de un Administrador (Sign Up)**  
   - Haz clic en **"Sign Up as Admin"** en la parte inferior del cuadro de Login.  
   - Ingresa un **username** y una **password**.  
   - Pulsa en **Sign Up**.  
   - Si se crea exitosamente, el sistema iniciará sesión automáticamente como administrador.  
   - Nota: Este registro únicamente crea usuarios en la tabla de administradores (`users`).  
   - Solo el primer administrador debería registrarse por este medio. Posteriormente, pueden gestionarse nuevos administradores directamente en la tabla `users` o extendiendo el sistema.

---

## Interfaz de Usuario

Una vez iniciada la sesión, se muestra un layout con dos paneles principales:

### Panel Lateral (Carpetas y Proyectos)
- **Ubicado a la izquierda**, con fondo oscuro.  
- Contiene la **estructura de carpetas** en forma de árbol.  
- Bajo cada carpeta se listan los **proyectos**.  
- Si eres **administrador**, verás un **botón de eliminar** (`X`) junto a cada carpeta o proyecto.  
- Al hacer clic en un **título de proyecto**, se mostrará su detalle en el **panel principal**.

En la parte inferior hay **botones** para:
- **Crear nueva carpeta** (si eres administrador).  
- **Crear nuevo proyecto** (si eres administrador).  
- **Gestionar clientes** (si eres administrador).

### Panel Principal (Contenido)
- **Ocupa el espacio principal a la derecha**, sobre fondo blanco.  
- Dependiendo de la selección, mostrará:
  - El listado de iteraciones de un proyecto.
  - La sección de administración de clientes.
  - Un mensaje de bienvenida si no se ha seleccionado un proyecto.

---

## Operaciones para Administradores

### Crear Carpeta
1. Haz clic en el botón **"+ Nueva carpeta"** en el panel lateral.  
2. Se abrirá un **modal**:
   - Ingresa el **nombre** de la carpeta.  
   - Selecciona la carpeta superior (opcional) para anidar la nueva carpeta.  
   - Pulsa **"Create Folder"**.  
3. La carpeta aparecerá en el árbol del panel lateral.

### Crear Proyecto
1. Haz clic en **"+ New Project"** en el panel lateral.  
2. En el **modal**:
   - Selecciona la **carpeta** a la que pertenecerá el proyecto.  
   - Indica el **título** del proyecto.  
   - Proporciona una **descripción**.  
   - Pulsa **"Crear proyecto"**.  
3. El proyecto aparecerá dentro de la carpeta seleccionada en el panel lateral.

### Crear Iteración
1. Accede a un proyecto haciendo clic en su nombre en el panel lateral.  
2. En el panel principal, se mostrará la sección “**Crear nueva iteración**”.  
   - Ingresa **Título**, **Descripción** y **selecciona un archivo** (preferiblemente .mp4).  
   - Pulsa **"Crear iteración"**.  
3. Se creará una nueva iteración que se listará en la parte superior de la página.

#### Editar / Eliminar Iteración
- Dentro de cada iteración, el administrador puede **editar** o **borrar**:
  - **Editar**: se abre un modal que permite cambiar título, descripción o subir un nuevo archivo.  
  - **Borrar**: el sistema elimina la iteración y todos los comentarios asociados.

### Gestión de Clientes
Para administrar clientes, haz clic en el botón **"Clientes"** en el panel lateral:
1. **Listado de clientes**: aparece una tabla con todos los clientes (username, nombre, email).
2. **Editar cliente**: pulsa en **"Editar"** (se abrirá un modal para cambiar username, nombre, email).
3. **Borrar cliente**: pulsa en **"Borrar"** (confirmar en la ventana emergente).
4. **Crear nuevo cliente**: en la parte inferior hay un formulario para ingresar un nuevo cliente con:
   - **username**, **contraseña**, **nombre** y **email**.

---

## Operaciones para Clientes

### Visualizar Iteraciones
1. Iniciar sesión como cliente (username y password registrados por el administrador).  
2. En el **panel lateral**, selecciona la carpeta y luego el **proyecto** deseado.  
3. En el **panel principal**, verás:
   - Título y descripción del proyecto.  
   - La **lista de iteraciones** disponibles.  
   - Cada iteración muestra:
     - Un **video** reproducible dentro del navegador.  
     - El botón **"Descargar video"** (opcional).  
     - Un formulario para **dejar un comentario**.

### Descargar Archivo (Video)
- Cada iteración incluye un enlace “**Descargar video**”.  
- Al hacer clic, el navegador iniciará la descarga del archivo.

### Comentar
- Debajo del video, hay un **formulario de comentario** con un campo de texto.  
- Ingresa tu comentario y pulsa en **"Enviar"**.  
- El comentario aparecerá abajo, junto con la fecha y la identificación del cliente.

---

## Cerrar Sesión (Logout)
Para **cerrar sesión**, haz clic en **"Logout"** en la esquina superior derecha (en la barra de navegación).  
- El sistema destruirá la sesión y te redirigirá a la página de inicio (login).

---

## Seguridad y Recomendaciones
1. **Contraseñas seguras**: se recomienda que administradores y clientes usen contraseñas robustas.  
2. **Permisos de escritura**: limitar permisos de escritura únicamente a la carpeta `uploads/` y al archivo de base de datos `projects.db`.  
3. **Copias de seguridad**: mantener backups periódicos de `projects.db` para no perder datos.  
4. **Validación de datos**: este ejemplo realiza la validación básica de formularios. Para entornos de producción, agregar controles adicionales de seguridad (tamaño de archivo, tipo MIME, etc.).  

---

## Contacto y Soporte
Para dudas, reportes de fallos o solicitar mejoras, puedes contactar a:

- **Nombre**: [Tu Nombre / Organización]  
- **Correo**: [Tu Email de Soporte]  
- **Sitio Web**: [URL del sitio si aplica]  

---

¡Eso es todo! Con esta guía, deberías poder instalar, configurar y utilizar el sistema de carpetas, proyectos e iteraciones de manera óptima.  
