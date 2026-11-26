# WareFlow — Warehouse Maintenance Management System

## Installation (XAMPP)

1. Copy the `warehouse-maintenance` folder to `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`, create/import the file `database/warehouse_maintenance.sql`.
4. Open `http://localhost/warehouse-maintenance/`.
5. If your MySQL password is not blank, edit `config/database.php`.

## Demo accounts

All demo users use password: `password`

| Role | Email |
| --- | --- |
| Admin | admin@wareflow.test |
| Maintenance Staff | tech1@wareflow.test |
| Warehouse Staff | warehouse1@wareflow.test |

## Main features

Role-based login, equipment management, request submission, work order updates with cost tracking, maintenance history, dashboard, reports, and placeholders for future AI and barcode integrations.
