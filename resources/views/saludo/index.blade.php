<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Hola, {{ $nombre }}!</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg space-y-6 p-10 bg-white rounded-xl shadow-2xl">
        <div class="text-center space-y-4">
            <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-3xl">👋</span>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-800">
                ¡Hola, {{ $nombre }}!
            </h1>
            
            <p class="text-lg text-gray-600">
                Qué bueno tenerte aquí. ¡Es un placer conocerte!
            </p>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-sm text-green-800">
                    💝 Este saludo fue creado especialmente para ti por nuestra aplicación Laravel.
                </p>
            </div>
        </div>
        
        <div class="flex justify-center space-x-4 pt-6">
            <a href="{{ route('saludo.create') }}"
               class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium">
                Enviar otro saludo
            </a>
            
            <a href="{{ route('welcome') }}"
               class="px-6 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors font-medium">
                Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>