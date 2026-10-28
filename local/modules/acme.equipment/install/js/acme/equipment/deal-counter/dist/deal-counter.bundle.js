(function (exports, main_core) {
    'use strict';

    const DealCounter = {
        mount(dealId, highlighted) {
            BX.ajax.runAction('acme:equipment.Equipment.count', {
                data: {dealId: dealId},
                method: 'GET'
            }).then((response) => this.render(response.data.count, highlighted));
        },

        render(count, highlighted, attempt = 0) {
            const clientControl = document.querySelector('[data-cid="CLIENT"]');
            if (!clientControl && attempt < 20) {
                setTimeout(() => this.render(count, highlighted, attempt + 1), 100);
                return;
            }
            if (!clientControl || document.getElementById('acme-equipment-count')) {
                return;
            }

            const node = main_core.Tag.render`
                <div id="acme-equipment-count" class="acme-equipment-count ${highlighted ? 'acme-equipment-count--highlighted' : ''}">
                    <span class="acme-equipment-count__label">Оборудование</span>
                    <span class="acme-equipment-count__value">${String(count)}</span>
                </div>`;
            clientControl.insertAdjacentElement('afterend', node);
        }
    };

    main_core.Reflection.namespace('Acme.Equipment').DealCounter = DealCounter;
}(this, BX));

