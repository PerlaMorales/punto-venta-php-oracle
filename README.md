# Sistema de Punto de Venta — PHP y Oracle

## 1. Información general

**Institución:** Universidad Mariano Gálvez de Guatemala
**Proyecto:** Sistema de Punto de Venta
**Tecnologías:** PHP, XAMPP, Apache y Oracle Database
**Editor:** Visual Studio Code

### Integrantes

* Perla Morales
* Erick Méndez
* Karent
* Gustabo
* Alex

## 2. Descripción del proyecto

El sistema de punto de venta permite administrar información comercial mediante módulos relacionados con productos, categorías, marcas, inventario, proveedores y consultas. La aplicación utiliza PHP para la lógica del sistema y Oracle Database para almacenar la información.

## 3. Requisitos

* Windows.
* XAMPP con PHP 8.2 o una versión compatible.
* Oracle Database configurado y funcionando.
* Oracle SQL Developer para administrar la base de datos.
* Extensión OCI8 habilitada en PHP.
* Visual Studio Code, si se desea consultar o modificar el código fuente.

## 4. Instalación

1. Descargar el repositorio de GitHub.
2. Copiar la carpeta del proyecto dentro de `C:\xampp\htdocs\`.
3. Configurar las credenciales de conexión a Oracle en `config/conexion.php`.
4. Verificar que Oracle Database esté funcionando y que exista el servicio `ORCLPDB`.
5. Iniciar Apache desde el panel de control de XAMPP.

## 5. Ejecución

Abrir el navegador y acceder a:

`http://localhost/punto_venta/`

Desde el menú principal se puede acceder a los módulos disponibles.

## 6. Módulos del proyecto

* Productos
* Categorías
* Marcas
* Inventario
* Proveedores
* Consultas y estadísticas

## 7. Base de datos

El diseño general del proyecto contempla 12 tablas:

* CATEGORIAS
* CLIENTES
* COMPRAS
* DETALLE_COMPRA
* DETALLE_VENTA
* INVENTARIO
* MARCAS
* METODOS_PAGO
* PRODUCTOS
* PROVEEDORES
* USUARIOS
* VENTAS

Para ejecutar el proyecto en otra computadora, se deben crear las tablas y sus relaciones en Oracle antes de utilizar la aplicación.

## 8. Repositorio

El código fuente del proyecto se encuentra publicado en GitHub. Agregar aquí la URL del repositorio.

## 9. Seguridad

No publicar contraseñas de Oracle ni otras credenciales privadas en el repositorio. Cada instalación debe configurar sus propias credenciales.

## 10. Equipo de desarrollo

Proyecto elaborado con fines académicos para la Universidad Mariano Gálvez de Guatemala.
