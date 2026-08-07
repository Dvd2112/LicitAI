document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-flash').forEach(function (alert) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 4000);
    });

    var newContractModal = document.getElementById('novoContratoModal');
    if (newContractModal) {
        newContractModal.addEventListener('show.bs.modal', function () {
            var form = newContractModal.querySelector('form');
            if (form) form.reset();
        });
    }

    document.querySelectorAll('input[data-cnpj]').forEach(function (input) {
        input.addEventListener('input', function () {
            var digits = input.value.replace(/\D/g, '').slice(0, 14);
            if (digits.length <= 12) {
                input.value = digits.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{4})(\d)/, '$1/$2');
            } else {
                input.value = digits.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
            }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.drop-zone').forEach(function (zone) {
        var input = document.getElementById(zone.dataset.target);
        if (!input) return;

        zone.addEventListener('click', function () { input.click(); });

        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                zone.classList.remove('dragover');
            });
        });

        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                var label = zone.querySelector('.drop-zone-files');
                if (label) label.textContent = Array.from(input.files).map(function (f) { return f.name; }).join(', ');
            }
        });

        input.addEventListener('change', function () {
            var label = zone.querySelector('.drop-zone-files');
            if (label) label.textContent = Array.from(input.files).map(function (f) { return f.name; }).join(', ');
        });
    });
});
