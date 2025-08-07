function w3_open() {
  document.getElementById("mySidebar").classList.add("show");
  document.getElementById("openNav").style.display = "none";
}

function w3_close() {
  document.getElementById("mySidebar").classList.remove("show");
  document.getElementById("openNav").style.display = "flex";
}
