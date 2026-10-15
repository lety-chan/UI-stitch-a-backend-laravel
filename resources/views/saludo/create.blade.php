<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Saludo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-8 bg-white rounded-xl shadow-lg">
        <h1 class="text-2xl font-bold text-center text-gray-800">
            ¡Saluda a alguien especial!
        </h1>
        
        <form action="{{ route('saludo.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label for="nombre" class="block mb-2 font-medium text-gray-700">
                    Tu nombre:
                </label>
                <input type="text" id="nombre" name="nombre"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="{{ old('nombre') }}" required>
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors font-medium">
                Enviar Saludo
            </button>
        </form>
        
        <p class="text-center text-sm text-gray-500">
            Ingresa tu nombre para recibir un saludo personalizado
        </p>
    </div>
</body>
</html>