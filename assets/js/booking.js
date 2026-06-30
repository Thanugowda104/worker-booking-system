let bookingData = {
    category_id: null,
    category_name: null,
    worker_id: null,
    worker_name: null,
    rate: 0,
    date: null,
    start_time: null,
    booking_type: 'request'
};

document.addEventListener('DOMContentLoaded', function () {
    const serviceCards = document.querySelectorAll('.service-card');

    serviceCards.forEach(card => {
        card.addEventListener('click', function () {
            bookingData.category_id = this.dataset.categoryId;

            serviceCards.forEach(c => c.classList.remove('border-primary'));
            this.classList.add('border-primary');

            // Move to step 2
            document.getElementById('step-1').classList.add('d-none');
            document.getElementById('step-2').classList.remove('d-none');

            console.log('Selected service:', bookingData.category_id);
        });
    });

    // Back button
    const backBtn = document.getElementById('backToStep1');
    if (backBtn) {
        backBtn.addEventListener('click', function () {
            document.getElementById('step-2').classList.add('d-none');
            document.getElementById('step-1').classList.remove('d-none');
        });
    }
});