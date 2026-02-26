# Document Manager

## Overview

Document Manager is a full-stack document management system built with:

-   **Backend:** Laravel (PHP)
-   **Frontend:** Vue 3 + Vite
-   **Database:** MySQL
-   **Storage:** Laravel public disk (storage/app/public)
-   **UI:** TailwindCSS + vue-select

The system allows users to:

-   Upload and manage documents
-   Assign documents to different owner types (Client, Carrier, Contact,
    Vehicle, etc.)
-   Soft delete and restore documents
-   Preview files inside a resizable viewer
-   Download files directly from the list
-   Filter by Active / Deleted
-   Search documents
-   Paginate results with direct page jump support

------------------------------------------------------------------------

# Project Structure

    document-manager/
    │
    ├── backend/        # Laravel application
    │   ├── app/
    │   ├── routes/
    │   ├── storage/
    │   └── ...
    │
    ├── frontend/       # Vue 3 + Vite application
    │   ├── src/
    │   └── ...
    │
    └── README.md

------------------------------------------------------------------------

# Backend (Laravel)

## Key Features

### 1. Document CRUD

-   Create document with file upload (multipart/form-data)
-   Update metadata and optionally replace file
-   Soft delete (is_deleted = 1)
-   Restore deleted document
-   List with pagination and filtering

### 2. Storage Logic

Files are stored in:

    storage/app/public/document-manager/{yy/mm}/filename.ext

Database stores:

    storage/document-manager/{yy/mm}/filename.ext

Frontend builds URL as:

    /storage/...

⚠ Important: Run this once in backend:

``` bash
php artisan storage:link
```

------------------------------------------------------------------------

## Routes

    GET    /document-manager/documents
    GET    /document-manager/lookups
    GET    /document-manager/owners/search

    POST   /document-manager/documents
    POST   /document-manager/documents/{id}
    POST   /document-manager/documents/{id}/delete
    POST   /document-manager/documents/{id}/restore

------------------------------------------------------------------------

# Frontend (Vue 3)

## Main Components

### Document Manager Page

-   Search
-   Status filter (Active / Deleted)
-   Paginated table
-   View / Download / Restore actions
-   Page jump
-   First / Prev / Next / Last navigation

### Document Modal

-   Create / Edit document
-   Owner type dependent dynamic owner search
-   Conditional expiration field (based on doctype.require_expire)
-   File preview
-   Resizable preview container that stretches modal

------------------------------------------------------------------------

# Pagination Logic

Frontend controls:

-   Page size
-   Direct page input
-   First / Last buttons
-   Automatic clamping of invalid page numbers

Backend uses Laravel paginator:

``` php
->paginate($limit, ['*'], 'page', $page)
```

------------------------------------------------------------------------

# Soft Delete Logic

Documents are NOT physically removed.

Instead:

    is_deleted = 1

Filters: - Active → is_deleted = 0 - Deleted → is_deleted = 1

Restore resets:

    is_deleted = 0

------------------------------------------------------------------------

# Owner Resolution

Documents support polymorphic ownership via:

    id_docowner
    id_owner

Supported owner tables:

-   carrier
-   clients
-   contact
-   vehicle

Owner name is dynamically resolved in DocumentsQuery.

------------------------------------------------------------------------

# Installation

## Backend

``` bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

## Frontend

``` bash
cd frontend
npm install
npm run dev
```

Frontend runs at:

    http://localhost:5173

Backend default:

    http://127.0.0.1:8000

------------------------------------------------------------------------

# API Integration

Axios wrapper located in:

    frontend/src/api/document-manager.js

All calls use:

    /document-manager/...

Ensure Vite proxy is configured correctly to avoid CORS issues.

------------------------------------------------------------------------

# Security Notes

-   File upload validated in StoreDocumentRequest
-   Update restricted to active documents
-   Deleted documents cannot be edited
-   Restore endpoint only works for deleted records

------------------------------------------------------------------------

# Future Improvements

-   Role-based access control
-   File versioning
-   Audit logs
-   Bulk upload
-   Permanent delete option
-   Drag & drop upload
-   Document tagging

------------------------------------------------------------------------

# Author

Norayr Kroyan

Full-stack Developer (Laravel + Vue)
