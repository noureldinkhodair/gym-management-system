const contactForm = document.getElementById('contactForm');

if (contactForm) {
  const contactError = document.getElementById('contact-error');
  const contactSuccess = document.getElementById('contact-success');

  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const name    = e.target.name.value.trim();
    const email   = e.target.email.value.trim();
    const phone   = e.target.phone.value.trim();
    const message = e.target.message.value.trim();

    if (contactError) contactError.textContent = '';
    if (contactSuccess) contactSuccess.textContent = '';

    if (!name || !email || !phone || !message) {
      if (contactError) contactError.textContent = 'Please fill all fields';
      return;
    }

    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!gmailRegex.test(email)) {
      if (contactError) contactError.textContent =
        'Email must be a valid Gmail address (example@gmail.com)';
      return;
    }

    if (!/^[0-9]{8,15}$/.test(phone)) {
      if (contactError) contactError.textContent =
        'Phone must contain digits only (8–15 numbers).';
      return;
    }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone_number', phone);
    formData.append('message', message);

    fetch('save_contact.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.text())
      .then(text => {
        if (text === 'OK') {
          if (contactSuccess) contactSuccess.textContent = 'Your message has been sent.';
          e.target.reset();
        } else {
          if (contactError) contactError.textContent = 'Error: ' + text;
        }
      })
      .catch(err => {
        console.error(err);
        if (contactError) contactError.textContent =
          'Server error, please try again later.';
      });
  });
}