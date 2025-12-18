# 🐐 GoatSport  
### Plataforma Profesional de Gestión de Canchas de Pádel

![PHP](https://img.shields.io/badge/PHP-Backend-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Completed-success)
![Type](https://img.shields.io/badge/Project-Academic%20%2F%20Portfolio-blue)

---

## 🎯 Descripción General

**GoatSport** es una plataforma web profesional para la **gestión integral de clubes de pádel**, desarrollada como proyecto académico avanzado y pensada para demostrar habilidades reales de **arquitectura, diseño de base de datos y lógica de negocio**.

El sistema cubre **reservas**, **pagos**, **torneos**, **ranking**, **promociones** y **control de usuarios**, aplicando un modelo robusto de roles y estados.

---

## 🚀 Funcionalidades Clave

- 🏟️ Gestión completa de canchas
- 📅 Reservas por turnos con validación de disponibilidad
- 💳 Pagos asociados a reservas
- 🏆 Torneos con generación de partidos por rondas
- 📊 Ranking automático de jugadores
- 🎉 Promociones y eventos especiales
- 🔔 Sistema de notificaciones
- 🧾 Reportes y control de incidencias
- 🔐 Control de acceso basado en roles

---

## 👥 Roles del Sistema

### 👤 Cliente
- Registro y autenticación
- Reserva de canchas
- Participación en partidos y torneos
- Gestión de pagos
- Perfil deportivo detallado
- Consulta de ranking y puntos
- Envío de reportes

### 🧾 Recepcionista
- Gestión operativa de reservas
- Confirmación y cancelación de turnos
- Atención diaria del club
- Visualización de pagos y reportes

### 🏟️ Proveedor
- Administración de clubes y canchas
- Definición de precios y horarios
- Creación de promociones y eventos
- Organización de torneos
- Gestión de recepcionistas

### 🛠️ Administrador
- Aprobación de proveedores y canchas
- Gestión global de usuarios
- Auditoría y control del sistema
- Administración manual de estados y puntos

---

## 🧱 Arquitectura y Diseño

- **Backend:** PHP
- **Base de Datos:** MySQL
- **Arquitectura:** Monolítica, orientada a dominio
- **Seguridad:** Roles, hashing, control de intentos
- **Integridad:** Claves foráneas y estados controlados
- **Modelo de datos:** Relacional normalizado

---

## 🗄️ Modelo de Datos (Resumen)

### Entidades Principales

| Entidad | Propósito |
|-------|----------|
| `usuarios` | Autenticación y roles |
| `canchas` | Infraestructura deportiva |
| `reservas` | Turnos y disponibilidad |
| `pagos` | Gestión financiera |
| `torneos` | Competencias |
| `partidos` | Encuentros |
| `participaciones` | Relación jugador-partido |
| `ranking` | Clasificación |
| `puntos_historial` | Historial de puntos |
| `promociones` | Descuentos |
| `eventos_especiales` | Eventos del club |
| `notificaciones` | Alertas |
| `reportes` | Incidencias |

---

## 🔐 Seguridad

- 🔒 Contraseñas hasheadas
- 🚫 Control de intentos de login
- 📧 Recuperación de contraseña con expiración
- 👮 Autorización por rol
- 🗑️ Eliminación en cascada controlada

---

## ⚙️ Instalación Rápida

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache / Nginx
- XAMPP o Laragon

### Pasos

```bash
git clone https://github.com/Empresa-UX/GoatSport.git
cd GoatSport
```
### Pasos

1. **Crear la base de datos MySQL**
2. **Importar el script SQL** con la estructura de tablas
3. **Configurar las credenciales de la base de datos** en el archivo de configuración del backend
4. **Iniciar el servidor local** (XAMPP, Laragon, Apache/Nginx)
5. **Acceder vía navegador** a la aplicación

---

## 🧪 Estados del Sistema

| Entidad | Estados |
|--------|---------|
| **Reservas** | `pendiente`, `confirmada`, `cancelada`, `no_show` |
| **Pagos** | `pendiente`, `pagado`, `cancelado` |
| **Canchas** | `pendiente`, `aprobado`, `denegado` |
| **Proveedores** | `pendiente`, `aprobado`, `rechazado` |
| **Torneos** | `abierto`, `en curso`, `cerrado`, `finalizado` |

---

## 📈 Roadmap Técnico

- [ ] API REST
- [ ] Pasarela de pagos real
- [ ] Estadísticas avanzadas
- [ ] Aplicación móvil
- [ ] Cache de disponibilidad
- [ ] Logs y métricas

---

## 👨‍💻 Autor

**Cristian Chejo**  
Desarrollador Full Stack

Proyecto desarrollado como parte de portafolio profesional, demostrando capacidades en diseño de sistemas, lógica de negocio y modelado de datos.

<div align="center">
**Proyecto orientado a demostrar arquitectura real y buenas prácticas**
</div>

## 📄 Licencia
Este proyecto se distribuye bajo la licencia MIT. Consulta el archivo `LICENSE` para más detalles.
