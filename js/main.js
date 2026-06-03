// Priced2GO – main.js

// Auto-format card number with spaces
document.addEventListener('input', function(e) {
    if (e.target.name === 'card_number') {
        let v = e.target.value.replace(/\D/g, '').substring(0, 16);
        e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
    }
    if (e.target.name === 'expiry') {
        let v = e.target.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
        e.target.value = v;
    }
});
