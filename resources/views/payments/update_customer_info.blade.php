{{-- resources/views/payments/update_customer_info.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Informações</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #5FA274;
        }

        .card {
            border-radius: 1rem;
        }

        .brand-color {
            color: #5FA274;
            /* verde do logo */
        }

        .btn-brand {
            background-color: #E8762C;
            /* laranja do logo */
            border: none;
        }

        .btn-brand:hover {
            background-color: #cf5d16;
        }

        .logo {
            display: block;
            margin: 0 auto 20px auto;
            max-width: 180px;
        }
    </style>
</head>

<body>

    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-lg p-4" style="max-width: 450px; width: 100%;">
            <div class="card-body">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Matriza" class="logo">
                <p class="text-center mb-4 brand-color fw-semibold">
                    Para prosseguir com a operação, precisamos que você atualize algumas informações abaixo:
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger p-2 mb-2 small">
                        {!! implode('<br>', $errors->all()) !!}
                    </div>
                @endif


                <form method="POST" action="{{ route('payments.save_customer_info') }}">
                    @csrf
                    <input type="hidden" name="empresa_id" value="{{ $data['customerCode'] }}">
                    <input type="hidden" name="plano_id" value="{{ $data['planCode'] }}">
                    <input type="hidden" name="metodo_pgto" value="{{ $data['paymentMethod'] }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">CPF / CNPJ</label>
                        <input type="text" id="cpfCnpj" name="cpf_cnpj" value="{{ old('cpf_cnpj') }}"
                            class="form-control form-control-lg" placeholder="Digite o CPF ou CNPJ" maxlength="18"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}"
                            class="form-control form-control-lg" placeholder="(00) 00000-0000" maxlength="15" required>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 btn-lg mt-3 text-white">
                        Salvar e Continuar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Máscaras JS -->
    <script>
        function maskCpfCnpj(value) {
            value = value.replace(/\D/g, '');
            if (value.length <= 11) { // CPF
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else { // CNPJ
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            return value;
        }

        function maskPhone(value) {
            value = value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            if (value.length <= 13) {
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            return value;
        }

        document.getElementById('cpfCnpj').addEventListener('input', function () {
            this.value = maskCpfCnpj(this.value);
        });

        document.getElementById('telefone').addEventListener('input', function () {
            this.value = maskPhone(this.value);
        });
    </script>

</body>

</html>