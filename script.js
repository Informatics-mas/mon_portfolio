function hamburg() {
    document.querySelector(".dropdown").style.display = "block";
}

function cancel() {
    document.querySelector(".dropdown").style.display = "none";
}

function downloadPDF() {
  const msg = document.getElementById("message");

  // Afficher le message
  msg.style.display = "block";

  // Cacher le message après 3s
  setTimeout(() => {
    msg.style.display = "none";
  }, 3000);

  // Téléchargement automatique
  const link = document.createElement("a");
  link.href = "https://drive.google.com/file/d/1DuVa9YQIQrJPAn7LcZC9ae3YJJCX4Jlm/view?usp=drivesdk";
  link.download = "CV-Davidson.pdf";

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}