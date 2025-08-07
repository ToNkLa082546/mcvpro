document.addEventListener("DOMContentLoaded", () => {
  
  const successTimeEl = document.getElementById("success-time");
  const errorTimeEl = document.getElementById("error-time");

  if (successTimeEl) {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const dateStr = now.toLocaleDateString('th-TH', options);
    const timeStr = now.toLocaleTimeString('th-TH');
    successTimeEl.innerText = `วันที่ ${dateStr} เวลา ${timeStr}`;
  }

  if (errorTimeEl) {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const dateStr = now.toLocaleDateString('th-TH', options);
    const timeStr = now.toLocaleTimeString('th-TH');
    errorTimeEl.innerText = `วันที่ ${dateStr} เวลา ${timeStr}`;
  }

});
