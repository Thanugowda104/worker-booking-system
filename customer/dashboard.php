customer

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth('customer');
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();
$showNavbar = true;

require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Book a Service</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

            <ul class="nav nav-pills nav-justified p-3 bg-light rounded-top">
                <li class="nav-item"><a class="nav-link active">Service</a></li>
                <li class="nav-item"><a class="nav-link">Worker</a></li>
                <li class="nav-item"><a class="nav-link">Date & Time</a></li>
                <li class="nav-item"><a class="nav-link">Confirm</a></li>
            </ul>

            <div class="p-4">

                <!-- STEP 1 -->
                <div id="step-1" class="booking-step">
                    <h4>Select a service</h4>

                    <div class="row g-3 mt-1">
                        <?php foreach ($categories as $cat): ?>
                            <div class="col-md-4">
                                <div class="card service-card shadow-sm"
                                     style="cursor:pointer"
                                     data-id="<?= $cat['id'] ?>"
                                     data-name="<?= htmlspecialchars($cat['name']) ?>">

                                    <div class="card-body text-center py-4">
                                        <i class="bi <?= $cat['icon'] ?: 'bi-grid' ?> fs-1 text-primary"></i>
                                        <h6><?= htmlspecialchars($cat['name']) ?></h6>
                                        <small><?= htmlspecialchars($cat['description'] ?? '') ?></small>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div id="step-2" class="booking-step d-none">
                    <button id="back1" class="btn btn-link">← Back</button>
                    <h4>Choose Worker for <span id="catName"></span></h4>

                    <div id="workerList" class="row mt-3"></div>
                </div>

                <!-- STEP 3 -->
                <div id="step-3" class="booking-step d-none">
                    <button id="back2" class="btn btn-link">← Back</button>

                    <input type="text" id="date" class="form-control mb-3">

                    <button id="loadSlots" type="button" class="btn btn-secondary">
                        Check Slots
                    </button>

                    <div id="slots" class="mt-3 d-flex flex-wrap gap-2"></div>
                </div>

                <!-- STEP 4 -->
                <div id="step-4" class="booking-step d-none">
                    <h4>Confirm Booking</h4>

                    <div class="card bg-light p-3 mb-3">
                        <p><b>Service:</b> <span id="confirmService"></span></p>
                        <p><b>Date:</b> <span id="confirmDate"></span></p>
                        <p><b>Time:</b> <span id="confirmTime"></span></p>
                    </div>

                    <form id="finalForm">
                        <button type="submit" class="btn btn-success w-100" id="confirmBtn">
                            Confirm Booking
                        </button>
                    </form>

                    <div id="loadingBox" class="alert alert-info mt-3 d-none">
                        Processing...
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

let booking = {};

function show(step){
    document.querySelectorAll(".booking-step").forEach(x => x.classList.add("d-none"));
    document.getElementById(step).classList.remove("d-none");
}

document.querySelectorAll(".service-card").forEach(card => {
    card.onclick = function(){

        booking.category_id = this.dataset.id;
        booking.category_name = this.dataset.name;

        document.getElementById("catName").innerText = booking.category_name;

        fetch("actions.php?action=fetch_workers&category_id=" + booking.category_id)
        .then(res => res.json())
        .then(data => {

            let html = "";

            if(data.length === 0){
                html = "<div>No workers found</div>";
            }

            data.forEach(w => {
                html += `
                    <div class="col-md-6">
                        <div class="card worker-card p-3 mb-2"
                             style="cursor:pointer"
                             data-id="${w.id}">
                            <h6>${w.name}</h6>
                            <small>₹${w.hourly_rate}/hr</small>
                        </div>
                    </div>
                `;
            });

            document.getElementById("workerList").innerHTML = html;

            document.querySelectorAll(".worker-card").forEach(worker => {
                worker.onclick = function(){
                    booking.worker_id = this.dataset.id;
                    show("step-3");
                };
            });

            show("step-2");

        });
    };
});

document.getElementById("back1").onclick = () => show("step-1");
document.getElementById("back2").onclick = () => show("step-2");

flatpickr("#date", {
    minDate: "today",
    dateFormat: "Y-m-d"
});

document.getElementById("loadSlots").onclick = function(){

    booking.date = document.getElementById("date").value;

    if(!booking.date){
        alert("Select date first");
        return;
    }

    fetch("actions.php?action=fetch_slots&worker_id=" + booking.worker_id + "&date=" + booking.date)
    .then(res => res.json())
    .then(data => {

        let html = "";

        if(data.length === 0){
            html = "<div>No slots available</div>";
        }

        data.forEach(slot => {
            html += `
                <button type="button"
                        class="btn btn-outline-primary slot-btn"
                        data-start="${slot.start_time}"
                        data-end="${slot.end_time}">
                    ${slot.start_time} - ${slot.end_time}
                </button>
            `;
        });

        document.getElementById("slots").innerHTML = html;

        document.querySelectorAll(".slot-btn").forEach(btn => {
            btn.onclick = function(){

                booking.start = this.dataset.start;
                booking.end = this.dataset.end;
                booking.booking_type = "slot";

                document.getElementById("confirmService").innerText = booking.category_name;
                document.getElementById("confirmDate").innerText = booking.date;
                document.getElementById("confirmTime").innerText = booking.start + " - " + booking.end;

                show("step-4");
            };
        });

    });
};

document.getElementById("finalForm").onsubmit = function(e){
    e.preventDefault();

    document.getElementById("confirmBtn").disabled = true;
    document.getElementById("loadingBox").classList.remove("d-none");

    fetch("actions.php", {
        method: "POST",
        headers: {
            "Content-Type":"application/json"
        },
        body: JSON.stringify({
            action:"create_booking",
            data: booking
        })
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){
            alert("Booking successful");
            window.location.href = "booking-confirm.php?id=" + data.booking_id;
        }else{
            alert(data.message);
            document.getElementById("confirmBtn").disabled = false;
            document.getElementById("loadingBox").classList.add("d-none");
        }

    })
    .catch(err => {
        console.log(err);
        alert("Server error");
    });
};

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
