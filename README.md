# ASSU VENTAS — Sistema de Ventas y Facturación

Sistema ERP web para la gestión integral de ventas, logística y facturación electrónica (SUNAT), diseñado para pequeñas y medianas empresas peruanas. Soporta múltiples empresas y sucursales con control de acceso por roles.

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.x · Laravel 10 |
| Frontend reactivo | Livewire 4 |
| UI | Bootstrap 5 · FontAwesome |
| Base de datos | MySQL 8 |
| Autenticación | Laravel Fortify · Spatie Permission |
| Reportes | FPDF · PhpSpreadsheet |
| Facturación | API SUNAT (e-factura / e-beta) |
| Imágenes | Intervention Image |

---

## Módulos del sistema

| Módulo | Descripción |
|---|---|
| **Configuración** | Menús, submenús, opciones, usuarios, roles, permisos, cajas, sucursales, empresas, planes |
| **Gestión de negocio** | Proveedores, familias y categorías de productos |
| **Logística** | Catálogo de productos, órdenes de compra, compras, movimientos de inventario |
| **Gestión de ventas** | Clientes, proformas, notas de venta, ventas, pagos en cuotas, registro de pagos |
| **Facturación electrónica** | Declaración a SUNAT, resúmenes diarios, historial de envíos, notas de crédito/débito, comunicación de bajas |
| **Reportes** | Ventas por caja, por vendedor, por cliente, productos más vendidos, control de pagos de cuotas |

---

## Estructura de carpetas

```
app/
├── Http/Controllers/     # Controladores de rutas (1 por módulo)
├── Livewire/             # Componentes reactivos (lógica + vista juntas)
│   ├── Auth/             # Login, recuperación de contraseña
│   ├── Configuracion/    # Usuarios, roles, menús, cajas, etc.
│   ├── Gestion/          # Proveedores, familias, categorías
│   ├── Logistica/        # Productos, compras
│   ├── Ventas/           # Clientes, ventas, proformas, pagos
│   ├── Facturacion/      # SUNAT, resúmenes, bajas
│   ├── Reporte/          # Reportes de negocio
│   └── Inicio/           # Dashboards por rol
├── Models/               # Modelos Eloquent
├── Service/              # Lógica de negocio reutilizable (PermisoService, etc.)
├── Exports/              # Clases para exportar Excel
└── Mail/                 # Correos transaccionales

database/
├── migrations/           # 50 migraciones ordenadas por dependencia FK
└── seeders/
    ├── data/             # JSON: ubigeo, permisos, role_has_permissions, model_has_roles
    ├── DatabaseSeeder.php
    ├── UbigeoSeeder.php
    ├── CatalogosSeeder.php
    ├── EmpresaSeeder.php
    ├── MenuSeeder.php
    ├── RolesPermisosSeeder.php
    ├── UsuariosSeeder.php
    └── CajaSerieSeeder.php

docs/                     # Documentación técnica del sistema
public/
├── uploads/              # Imágenes de productos
├── usuarios/             # Fotos de perfil
└── comprobantes_ventas/  # PDFs generados
```

---

## Base de datos — tablas principales

### Autenticación y acceso

| Tabla | Descripción |
|---|---|
| `users` | Usuarios del sistema (PK: `id_users`) |
| `persona` | Datos personales vinculados a usuarios |
| `roles` | Roles del sistema (Spatie + columnas custom) |
| `permissions` | Permisos granulares por opción de menú |
| `model_has_roles` | Asignación usuario → rol |
| `role_has_permissions` | Asignación rol → permisos |
| `user_sucursal` | Pivot usuario ↔ sucursal (multi-sucursal) |

### Empresa y configuración

| Tabla | Descripción |
|---|---|
| `empresa` | Datos de la empresa (RUC, certificado SUNAT) |
| `sucursals` | Sucursales por empresa |
| `empresa_planes` | Plan activo de la empresa |
| `planes` | Tipos de plan disponibles |
| `menus` / `submenu` / `opciones` | Árbol de navegación y control de permisos |
| `caja_numero` | Cajas registradoras por sucursal |
| `serie` | Series y correlativos por tipo de comprobante |

### Catálogos

| Tabla | Descripción |
|---|---|
| `medida` | Unidades de medida |
| `tipo_documento` | DNI, RUC, CE, pasaporte, etc. |
| `tipo_pago` | Efectivo, transferencia, tarjeta, etc. |
| `tipo_afectacion` | Códigos SUNAT de afectación IGV |
| `monedas` | Sol, dólar, euro |
| `ubigeo` | 1 873 registros de ubigeos peruanos |

### Inventario y compras

| Tabla | Descripción |
|---|---|
| `productos` | Catálogo de productos |
| `familia` / `categoria` | Clasificación de productos |
| `proveedores` | Proveedores |
| `orden_compra` / `detalle_compra` | Órdenes y detalles de compra |
| `movimiento_productos` | Entradas y salidas de inventario |
| `stock` | Stock por producto y sucursal |

### Ventas y facturación

| Tabla | Descripción |
|---|---|
| `ventas` | Cabecera de venta (factura, boleta, nota de venta) |
| `ventas_detalle` | Líneas de la venta |
| `ventas_cuotas` | Cuotas de pago (ventas al crédito) |
| `pagos_cuotas` | Pagos registrados contra cuotas |
| `cliente` / `persona` | Datos del cliente |
| `proforma` / `proforma_detalle` | Cotizaciones |
| `envio_resumen` | Resúmenes y comunicaciones enviadas a SUNAT |
| `envio_resumen_detalle` | Detalle de cada envío |

### Relaciones clave

```
empresa ──< sucursals ──< user_sucursal >── users
empresa ──< empresa_planes >── planes
sucursals ──< caja_numero ──< serie
caja_numero ──< ventas ──< ventas_detalle
ventas ──< ventas_cuotas ──< pagos_cuotas
productos >── categoria >── familia
productos >── medida
ventas >── tipo_afectacion
```

---

## Roles del sistema

| ID | Nombre | Acceso |
|---|---|---|
| 1 | **superadmin** | Total — puede ver y operar todas las empresas |
| 2 | **Administrador** | Restringido a su empresa |
| 3 | **Vendedor** | Restringido a su sucursal activa |
| 4 | **Contador** | Restringido a su empresa, enfocado en reportes y facturación |

Ver [`docs/flujo-empresa-sucursal.md`](docs/flujo-empresa-sucursal.md) para el detalle de cómo se aplica la restricción por rol en todo el sistema.

---

## Levantar el sistema

### Requisitos previos

- PHP >= 8.1
- MySQL >= 8.0
- Composer >= 2
- Node.js >= 18 (solo para compilar assets)
- Laragon o servidor local equivalente

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio>
cd proyecto
composer install
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` y completar al menos:

```env
APP_URL=http://proyecto.test/

DB_DATABASE=proyecto_bd
DB_USERNAME=root
DB_PASSWORD=

MAIL_HOST=smtp.tuservidor.com
MAIL_USERNAME=tu@correo.com
MAIL_PASSWORD=tu_password

# Test SUNAT (cambiar a producción cuando corresponda)
APP_FACT=https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
APP_FACT_LOCAL=1
APP_CACERT_FACT=1
```

### 3. Crear la base de datos y cargar datos iniciales

```bash
php artisan migrate
php artisan db:seed
```

Esto crea todas las tablas y carga:
- Ubigeo completo de Perú (1 873 registros)
- Catálogos: unidades, tipos de pago, afectaciones SUNAT, monedas
- Empresa y sucursales de ejemplo
- Menú completo del sistema
- 4 roles con sus permisos asignados
- 4 usuarios de prueba

### 4. Usuarios iniciales

| Usuario | Contraseña | Rol |
|---|---|---|
| `superadmin` | `12345678` | Superadmin |
| `admin` | `12345678` | Administrador |
| `vendedor` | `12345678` | Vendedor |
| `contador` | `12345678` | Contador |

> Cambiar las contraseñas en el primer uso en producción.

### 5. Configurar el certificado SUNAT (producción)

En la tabla `empresa`, completar los campos:
- `empresa_usuario_sol` — usuario SOL de SUNAT
- `empresa_clave_sol` — clave SOL
- `empresa_ruta_certificado` — contenido del certificado `.pem`
- `empresa_clave_certificado` — clave privada del certificado

---

## Documentación técnica

| Documento | Contenido |
|---|---|
| [`docs/flujo-empresa-sucursal.md`](docs/flujo-empresa-sucursal.md) | Cómo se restringe el acceso a datos por empresa/sucursal según el rol |

---

## Ramas del repositorio

| Rama | Propósito |
|---|---|
| `main` | Código estable |
| `refactor/ventas` | Refactorización activa del módulo de ventas con soporte multisucursal |
