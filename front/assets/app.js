document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-flash').forEach(function (alert) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 4000);
    });

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('show.bs.modal', function () {
            var form = modal.querySelector('form');
            if (form) form.reset();
            modal.querySelectorAll('.drop-zone-files').forEach(function (label) {
                label.textContent = 'Nenhum arquivo selecionado';
            });
        });
    });

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

    document.querySelectorAll('.drop-zone, .upload-area').forEach(function (zone) {
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
                var label = zone.querySelector('.drop-zone-files, .upload-files');
                if (label) label.textContent = Array.from(input.files).map(function (f) { return f.name; }).join(', ');
            }
        });

        input.addEventListener('change', function () {
            var label = zone.querySelector('.drop-zone-files, .upload-files');
            if (label) label.textContent = Array.from(input.files).map(function (f) { return f.name; }).join(', ');
        });
    });

    /* ============ Wizard de cadastro de edital ============ */
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.wizard-tab'));
    var steps = Array.prototype.slice.call(document.querySelectorAll('.step-section'));
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var btnEdit = document.getElementById('btnEdit');
    var formEdital = document.getElementById('formEdital');
    var current = 1;
    var totalSteps = steps.length;

    function formatBRL(value) {
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function recalcTotals() {
        var total = 0;
        document.querySelectorAll('.item-row').forEach(function (row) {
            var qtd = parseFloat(row.querySelector('.item-qtd').value) || 0;
            var unit = parseFloat(row.querySelector('.item-unit').value) || 0;
            var subtotal = qtd * unit;
            row.querySelector('.item-total').value = formatBRL(subtotal);
            total += subtotal;
        });
        var box = document.getElementById('valorTotalBox');
        if (box) box.textContent = formatBRL(total);
        var boxItens = document.getElementById('itensTotalBox');
        if (boxItens) boxItens.textContent = formatBRL(total);
        var hidden = document.getElementById('totalHidden');
        if (hidden) hidden.value = total.toFixed(2);
    }

    function bindItemRow(row) {
        row.querySelectorAll('.item-qtd, .item-unit').forEach(function (input) {
            input.addEventListener('input', recalcTotals);
        });
        var remove = row.querySelector('.btn-remove-item');
        if (remove) {
            remove.addEventListener('click', function () {
                var rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    row.remove();
                    recalcTotals();
                }
            });
        }
    }

    var addItemBtn = document.getElementById('btnAddItem');
    if (addItemBtn && formEdital) {
        document.querySelectorAll('.item-row').forEach(bindItemRow);
        var itemTemplate = document.getElementById('itemTemplate');

        addItemBtn.addEventListener('click', function () {
            var clone = itemTemplate.content.firstElementChild.cloneNode(true);
            clone.querySelectorAll('input, select').forEach(function (el) {
                el.value = '';
                if (el.type === 'number') el.value = '0';
            });
            clone.querySelector('.item-total').value = '';
            document.getElementById('itensBody').appendChild(clone);
            bindItemRow(clone);
        });
    }

    var addLocalBtn = document.getElementById('btnAddLocal');
    var localTemplate = document.getElementById('localTemplate');
    if (addLocalBtn && localTemplate) {
        addLocalBtn.addEventListener('click', function () {
            var clone = localTemplate.content.firstElementChild.cloneNode(true);
            clone.querySelectorAll('input, textarea, select').forEach(function (el) {
                el.value = '';
            });
            document.getElementById('locaisBody').appendChild(clone);
        });
    }

    function refreshReview() {
        function value(id) {
            var el = document.getElementById(id);
            return el ? el.value.trim() : '';
        }
        function set(id, text) {
            var el = document.getElementById(id);
            if (el) el.textContent = text;
        }

        var licenca = document.getElementById('w_licenca');
        var nLocais = document.querySelectorAll('#locaisBody .local-row').length;
        var nItens = document.querySelectorAll('#itensBody .item-row').length;
        var pagDias = value('w_pagamento_dias');
        var regraValor = parseFloat(value('w_regra_valor')) || 0;
        var total = parseFloat(value('totalHidden')) || 0;

        set('rv_numero', value('w_numero') || '—');
        set('rv_ano', value('w_ano') || '—');
        set('rv_tipo', value('w_tipo') || '—');
        set('rv_situacao', value('w_situacao') || 'Em licitação');
        set('rv_locais', String(nLocais || 1));
        set('rv_itens', String(nItens || 1));
        set('rv_licenca', licenca && licenca.checked ? 'Sim' : 'Não');
        set('rv_titulo', value('w_titulo') || '—');
        set('rv_pessoa', value('w_entidade') || '—');

        set('rv_site', value('w_site') || 'Sem link');
        set('rv_forma', value('w_forma_pagamento') || 'Não definido');
        set('rv_pagamento', pagDias !== '' && pagDias !== '0' ? pagDias + ' dias' : 'Não definido');
        set('rv_dap', formatBRL(regraValor));
        set('rv_total', formatBRL(total));
    }

    function goTo(step) {
        current = Math.max(1, Math.min(totalSteps, step));
        tabs.forEach(function (tab, i) {
            tab.classList.toggle('active', i === current - 1);
        });
        steps.forEach(function (section, i) {
            section.classList.toggle('active', i === current - 1);
        });

        if (current === totalSteps) refreshReview();
        if (btnPrev) btnPrev.disabled = current === 1;
        if (btnNext) {
            if (current === totalSteps) {
                btnNext.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Gravar';
                btnNext.classList.remove('btn-outline-info');
                btnNext.classList.add('btn-success');
            } else {
                btnNext.innerHTML = 'Avançar<i class="ti ti-chevron-right ms-1"></i>';
                btnNext.classList.remove('btn-success');
                btnNext.classList.add('btn-outline-info');
            }
        }
        if (btnEdit) btnEdit.style.display = current === totalSteps ? '' : 'none';

        var wizard = document.querySelector('.wizard-card');
        if (wizard) wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { goTo(i + 1); });
    });

    if (btnPrev) btnPrev.addEventListener('click', function () { goTo(current - 1); });
    if (btnNext) {
        btnNext.addEventListener('click', function () {
            if (current === totalSteps) {
                if (formEdital) formEdital.submit();
            } else {
                goTo(current + 1);
            }
        });
    }

    if (formEdital) formEdital.addEventListener('submit', function () {
        document.getElementById('btnNext').disabled = true;
    });

    goTo(1);
    recalcTotals();
});
