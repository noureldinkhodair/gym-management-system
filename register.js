console.log('register.js loaded');

const form = document.getElementById('registerForm');
const msgEl = document.getElementById('msg');

form.addEventListener('submit', function (e) {
  e.preventDefault();

  const fullName   = form.name.value.trim();
  const email      = form.gmail.value.trim();
  const phone      = form.phone_number.value.trim();
  const age        = form.age.value.trim();
  const password   = form.password.value.trim();
  const membership = form.subscription_id.value;
  const gym        = form.gym_id.value;

  msgEl.style.color = '#ff5252';
  msgEl.textContent = '';

  if (!fullName || !email || !phone || !age || !password || !membership || !gym) {
    msgEl.textContent = 'Please fill all required fields';
    return;
  }

  const nameRegex  = /^[A-Za-z\u0600-\u06FF\s]+$/;
  if (!nameRegex.test(fullName)) {
    msgEl.textContent = 'Name must contain letters only (Arabic or English).';
    return;
  }

  const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
  if (!gmailRegex.test(email)) {
    msgEl.textContent = 'Email must be a valid Gmail address (example@gmail.com).';
    return;
  }

  if (!/^[0-9]{8,15}$/.test(phone)) {
    msgEl.textContent = 'Phone must contain digits only (8–15 numbers).';
    return;
  }

  if (!/^[0-9]{1,3}$/.test(age)) {
    msgEl.textContent = 'Age must be a number.';
    return;
  }

  const passRegex = /^(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}$/;
  if (!passRegex.test(password)) {
    msgEl.textContent =
      'Password must be at least 8 characters, include one uppercase letter and one symbol (!@#$%^&*).';
    return;
  }

  const pendingRegistration = {
    name: fullName,
    gmail: email,
    phone_number: phone,
    age: age,
    password: password,
    subscription_id: membership,
    gym_id: gym
  };

  localStorage.setItem('pendingRegistration', JSON.stringify(pendingRegistration));

  window.location.href = 'payment.html';
});

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('reg') === 'success') {
    msgEl.style.color = '#4caf50';
    msgEl.textContent = 'Registration successful. Welcome to Star Gym!';
  }
});