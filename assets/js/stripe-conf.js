bkforb_gcal['stripeHandler'] = StripeCheckout.configure({
    key: bkforb_payment_system.pkey,
    image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
    locale: 'auto',
    token: function(token) {
        if(bkforb_gcal['receiveStripeToken'])bkforb_gcal['receiveStripeToken'](token);
    }
});
window.onpopstate = function(){
    bkforb_gcal['stripeHandler'].close();
};

bkforb_gcal['stripeOnPayButtonClick'] = function (options, callback) {
    bkforb_gcal['receiveStripeToken'] = function (token) {
        callback({'stripeToken':token.id});
    };
    bkforb_gcal['stripeHandler'].open({
        description: options.description,
        amount: options.amount,
        currency: options.currency,
        email: options.email
    });
};

/*
if (ps['name'] === 'stripe') {
    bkforb_gcal['stripeHandler'].open({
        description: 'Booking '+date_string+' - '+formatMinutes(time),
        amount: Math.round(recalcPrice(wrapper)*100),
        currency: currency,
        email: $.trim(form.find('[name=email]').val())
    });
    bkforb_gcal['receiveStripeToken'] = function (token) {
        sendBookingForm(wrapper, {'stripeToken':token.id});
    };
}*/
