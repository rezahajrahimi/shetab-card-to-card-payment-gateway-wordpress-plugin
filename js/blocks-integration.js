const settings = window.wc.wcSettings.getSetting('shetab_card_to_card_data', {});

const ShetabComponent = () => {
    return window.wp.htmlEntities.decodeEntities(settings.description || '');
};

const shetabCardPaymentMethod = {
    name: 'shetab_card_to_card',
    label: window.wp.htmlEntities.decodeEntities(settings.title || 'پرداخت کارت به کارت'),
    content: ShetabComponent,
    edit: ShetabComponent,
    canMakePayment: () => true,
    ariaLabel: window.wp.htmlEntities.decodeEntities(settings.title || 'پرداخت کارت به کارت'),
    supports: {
        features: settings.supports || [],
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(shetabCardPaymentMethod); 