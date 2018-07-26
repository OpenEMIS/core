//Gallery v.1.0.0

var Gallery = {
    init: function() {
        this.setGalleryModal();
    },

    setGalleryModal: function() {
        $(document).ready(function() {
            // console.log($('.img-wrapper')[0]);
            if ($('.img-wrapper')[0] != undefined) {

                var defaultShift = $('.img-wrapper')[0].offsetWidth;
                var reachEnd = false;
                var moveShift = JSON.parse(JSON.stringify(defaultShift));
                var $item = $('.img-wrapper .column'), //Cache your DOM selector
                    visible = 2, //Set the number of items that will be visible
                    index = 0, //Starting index
                    endIndex = ($item.length / visible) - 1; //End index
                var secondIndex = JSON.parse(JSON.stringify(endIndex))
                var nextBtn = document.getElementById("arrowR");
                var prevBtn = document.getElementById("arrowL");
                var bodyDir = getComputedStyle(document.body).direction;

                if (bodyDir == 'ltr') {
                    if ($('.img-wrapper')[0].scrollLeft == 0) {
                        nextBtn.className = "next-arrow";
                        prevBtn.className = "prev-arrow disabled";
                    }

                    $('#arrowR').click(function() {
                        if (index < endIndex) {
                            prevBtn.className = "prev-arrow";
                            if (secondIndex < 1) {
                                moveShift = defaultShift / 2;
                            } else {
                                moveShift = defaultShift;
                            }
                            index++;
                            secondIndex--;
                            if (secondIndex <= 0) {
                                nextBtn.className = "next-arrow disabled";
                                prevBtn.className = "prev-arrow";
                                reachEnd = true;
                            }
                            $item.animate({ 'left': '-=' + moveShift }, 400, 'linear');
                        }
                    });

                    $('#arrowL').click(function() {
                        if (index > 0) {
                            nextBtn.className = "next-arrow";
                            index--;
                            secondIndex++;

                            if (secondIndex == endIndex && $item.length % 2 == 1 && reachEnd == true) {
                                //check for the last shift and whether the amount of item is odd (if odd that means only 1 item left) and user have click all the way to the right
                                moveShift = defaultShift / 2;
                                reachEnd = false;
                            } else {
                                moveShift = defaultShift;
                            }
                            $item.animate({ 'left': '+=' + moveShift }, 400, 'linear');
                        }
                        if (index == 0 && $item.length > 2) {
                            nextBtn.className = "next-arrow";
                            prevBtn.className = "prev-arrow disabled";
                        }
                    });
                } else {
                    if (reachEnd = true) {
                        nextBtn.className = "next-arrow disabled";
                        prevBtn.className = "prev-arrow";
                    }
                    $('#arrowR').click(function() {
                        if (index > 0) {
                            nextBtn.className = "next-arrow";
                            index--;
                            secondIndex++;

                            if (secondIndex == endIndex && $item.length % 2 == 1 && reachEnd == true) {
                                //check for the last shift and whether the amount of item is odd (if odd that means only 1 item left) and user have click all the way to the right
                                moveShift = defaultShift / 2;
                                reachEnd = false;
                            } else {
                                moveShift = defaultShift;
                            }
                            $item.animate({ 'left': '-=' + moveShift }, 400, 'linear');
                        }
                        if (index == 0 && $item.length > 2) {
                            nextBtn.className = "next-arrow disabled";
                            prevBtn.className = "prev-arrow";
                        }
                    });

                    $('#arrowL').click(function() {
                        if (index < endIndex) {
                            prevBtn.className = "prev-arrow";
                            if (secondIndex < 1) {
                                moveShift = defaultShift / 2;
                            } else {
                                moveShift = defaultShift;
                            }
                            index++;
                            secondIndex--;
                            if (secondIndex <= 0) {
                                nextBtn.className = "next-arrow";
                                prevBtn.className = "prev-arrow disabled";
                                reachEnd = true;
                            }
                            $item.animate({ 'left': '+=' + moveShift }, 400, 'linear');
                        }
                    });
                }
                if ($item.length <= 2) {
                    nextBtn.className = "next-arrow disabled";
                    prevBtn.className = "prev-arrow disabled";
                }

            }
        });
    }
}

function openModal() {
    document.getElementById('myModal2').style.display = "block";
}

function closeModal() {
    document.getElementById('myModal2').style.display = "none";
}

var slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    var i;
    var slides = document.getElementsByClassName("mySlides");
    if (n > slides.length) { slideIndex = 1 }
    if (n < 1) { slideIndex = slides.length }
    for (i = 0; i < slides.length; i++) {
        slides[i].style.opacity = "0";
        slides[i].style.width = "0";
        slides[i].style.height = "0";
    }
    if (slides[slideIndex - 1] != undefined) {
        slides[slideIndex - 1].style.opacity = "1";
        slides[slideIndex - 1].style.width = "auto";
        slides[slideIndex - 1].style.height = "auto";
    }
}
var $item2 = $('.image-holder');
