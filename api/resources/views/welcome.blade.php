<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenEMIS Core API Home Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
        body {
            background-color: #ffffff;
            font-family: Arial, sans-serif;
        }

        h1 {
            color: #6c757d;
            font-weight: 400;
        }

        .container {
            text-align: center;
        }

        img {
            max-width: 100px;
            height: auto;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.5rem;
            }

            img {
                max-width: 80px;
            }
        }

    </style>
</head>
<body>
    <div class="container text-center d-flex align-items-center justify-content-center vh-100">
        <div>
            <img src="public/oe-logo.png" alt="Logo" class="img-fluid mb-4">
            <h1 class="display-4">OpenEMIS Core</h1>
            <h1 class="display-4">API Home Page</h1>
            {{-- POCOR-9602: Display version number from core/version file --}}
            <p class="text-muted mt-3">Version {{ $version }}</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
