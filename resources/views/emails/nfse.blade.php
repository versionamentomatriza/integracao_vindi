<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f7f7f7;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 { color: #444; }
        a {
            color: #0d6efd;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Olá, {{ $name }}!</h2>
        <p>Sua NFS-e número <strong>{{ $number }}</strong> foi emitida com sucesso.</p>

        @if(!empty($link))
            <p>Você pode consultar sua NFS-e através do link abaixo:</p>
            <p><a href="{{ $link }}" target="_blank">{{ $link }}</a></p>
        @endif

        <p>O arquivo PDF da NFS-e está anexado a este e-mail.</p>
        <p>Obrigado por utilizar nossos serviços!</p>
    </div>
</body>
</html>
