// Toggle Profile Dropdown Menu
function toggleDropdown() {
    var menu = document.getElementById("profileMenu");
    menu.classList.toggle("show-menu");
}

// Close dropdown if user clicks outside of it
window.onclick = function (event) {
    if (!event.target.matches('.profile-container') && !event.target.matches('.profile-icon') && !event.target.matches('.dropdown-arrow')) {
        var dropdowns = document.getElementsByClassName("dropdown-menu");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show-menu')) {
                openDropdown.classList.remove('show-menu');
            }
        }
    }
};

// Features Role Slider Logic
var currentSlide = 0;

function changeSlide(direction) {
    var slides = document.querySelectorAll(".role-slide");
    if (slides.length === 0) return;

    slides[currentSlide].classList.remove("active");
    currentSlide = (currentSlide + direction + slides.length) % slides.length;
    slides[currentSlide].classList.add("active");
}