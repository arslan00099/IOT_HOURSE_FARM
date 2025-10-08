<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Not logged in — redirect to login page
    header("Location: index.html");
    exit();
}

include('config.php');
// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user name
$sql = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row['name']);
} else {
    $username = "Unknown User";
}

//$horses = $conn->query("SELECT id, name, image_path FROM horses WHERE user_id = $user_id");

$horses = "SELECT id, name, image_path FROM horses WHERE user_id = ?";
$stmt = $conn->prepare($horses);
if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("❌ Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

// DEBUG: Check if any horses returned
if ($result->num_rows === 0) {
   // echo "<p>⚠️ No horses found for this user (user_id = $user_id).</p>";
}

$horseQuery = "SELECT id, name FROM horses WHERE user_id = ?";
$horseStmt = $conn->prepare($horseQuery);
$horseStmt->bind_param("i", $user_id);
$horseStmt->execute();
$horseResult = $horseStmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ostler Horse Monitoring - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c6e4a 0%, #1a4c33 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3e8a5e 0%, #2c6e4a 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(62, 138, 94, 0.3);
        }

        .horse-card {
            transition: all 0.3s ease;
        }

        .horse-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .feed-counter {
            background: linear-gradient(135deg, #f9f9f9 0%, #e9e9e9 100%);
        }
    </style>

    <script>
let cameraSocket;
let showingModalImg1 = true;
let modalImg1, modalImg2;

function openCameraModal(title = "Live Camera") {
    document.getElementById("camera-modal-title").innerText = title;
    document.getElementById("camera-modal").classList.remove("hidden");

    // Create two img elements for double buffering
    const container = document.querySelector("#camera-modal .relative");
    container.innerHTML = `
        <img id="modal-img1" class="absolute h-full w-full object-cover" />
        <img id="modal-img2" class="absolute h-full w-full object-cover hidden" />
    `;
    modalImg1 = document.getElementById("modal-img1");
    modalImg2 = document.getElementById("modal-img2");

    cameraSocket = new WebSocket("ws://" + location.hostname + ":8080");
    cameraSocket.binaryType = "blob";

    cameraSocket.onmessage = (event) => {
        const blob = new Blob([event.data], { type: "image/jpeg" });
        const nextImg = showingModalImg1 ? modalImg2 : modalImg1;
        const currentImg = showingModalImg1 ? modalImg1 : modalImg2;

        nextImg.onload = () => {
            nextImg.classList.remove("hidden");
            currentImg.classList.add("hidden");
            URL.revokeObjectURL(currentImg.src);
            showingModalImg1 = !showingModalImg1;
        };
        nextImg.src = URL.createObjectURL(blob);
    };
}

function closeModal(id) {
    document.getElementById(id).classList.add("hidden");
    if (cameraSocket) {
        cameraSocket.close();
        cameraSocket = null;
    }
}



</script>

</head>

<body class="bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <div class="sidebar w-64 text-white min-h-screen flex flex-col">
        <div class="p-5 flex items-center">
            <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <path d="M19 5V19H5V5H19Z" />
                <path d="M5 12H19" />
                <path d="M12 5V19" />
                <circle cx="17" cy="7" r="1" fill="currentColor" />
                <circle cx="7" cy="17" r="1" fill="currentColor" />
            </svg>
            <span class="ml-2 text-xl font-bold">Ostler</span>
        </div>
        <div class="flex-1 overflow-y-auto">
            <nav class="mt-5 px-2">
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md bg-green-700 bg-opacity-25">
                    <i class="fas fa-home mr-3 text-lg"></i>
                    Dashboard
                </a>
                <!-- <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1">
                    <i class="fas fa-horse mr-3 text-lg"></i>
                    Horses
                </a>
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1">
                    <i class="fas fa-calendar-alt mr-3 text-lg"></i>
                    Schedule
                </a>
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1">
                    <i class="fas fa-chart-line mr-3 text-lg"></i>
                    Analytics
                </a> -->
                <hr class="border-green-800 my-4">
                <h3 class="px-3 text-xs font-semibold text-green-200 uppercase tracking-wider">
                    Management
                </h3>
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1"
                    onclick="showModal('add-feeder-modal')">
                    <i class="fas fa-plus-circle mr-3 text-lg"></i>
                    Add Feeder
                </a>
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1"
                    onclick="showModal('add-horse-modal')">
                    <i class="fas fa-plus-circle mr-3 text-lg"></i>
                    Add Horse
                </a>
                <a href="#"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white hover:bg-green-700 hover:bg-opacity-25 mt-1"
                    onclick="showModal('edit-horse-modal')">
                    <i class="fas fa-edit mr-3 text-lg"></i>
                    Edit Horse
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-green-800">
            <div class="flex items-center">
                <!-- <img class="h-8 w-8 rounded-full bg-white" src="/api/placeholder/40/40" alt="User Avatar"> -->
                <img class="h-8 w-8 rounded-full bg-white"
                 <img src="uploads/mg.png" alt="User Avatar">

                <div class="ml-3">
                    <p class="text-sm font-medium text-white"><?php echo $username; ?></p>
                    <p class="text-xs text-green-200">Farm Manager</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo $username; ?></h1>
                <div>
                    <button id="notifications-button"
                        class="p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                    </button>
                    <button id="settings-button"
                        class="ml-3 p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-cog text-lg"></i>
                    </button>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex space-x-4">
                <button id="see-button"
                    class="btn-primary py-2 px-6 text-black font-medium rounded-lg flex items-center text-lg"
                    onclick="switchView('see-view')">
                    <i class="fas fa-video mr-2"></i> See
                </button>
                <button id="feed-button"
                    class="bg-white py-2 px-6 text-gray-700 font-medium rounded-lg border border-gray-300 flex items-center text-lg hover:bg-gray-50"
                    onclick="switchView('feed-view')">
                    <i class="fas fa-utensils mr-2"></i> Feed
                </button>
            </div>
        </header>
<!-- Main Content Area -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- See View -->
    <div id="see-view" class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">

    <?php
    while ($row = $result->fetch_assoc()):
        $horseName = htmlspecialchars($row['name']);
        $horseImage = !empty($row['image_path']) ? $row['image_path'] : 'default-horse.jpg';
        $horseId = htmlspecialchars($row['id']);
    ?>
        <div class="horse-card bg-white rounded-lg shadow overflow-hidden cursor-pointer"
             onclick="openCameraModal('<?php echo $horseName; ?>')"
             style="height: 300px;">
            <div class="relative w-full h-full">
                <img class="absolute h-full w-full object-cover"
                     src="<?php echo $horseImage; ?>"
                     alt="<?php echo $horseName; ?> Camera">

                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                    <h3 class="text-white font-medium"><?php echo $horseName; ?></h3>
                </div>
                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full">
                    online
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>





           <!-- Feed View (Hidden by default) -->
<div id="feed-view" class="hidden">
    <div id="horse-feed-list" class="bg-white rounded-lg shadow divide-y divide-gray-200">
        <!-- Horse Feed Items will be inserted here -->
    </div>
</div>
<script>
function loadFeedView() {
    fetch('get_feed_data.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return alert('Failed to load feed data');
            
            const container = document.getElementById('horse-feed-list');
            container.innerHTML = ''; // Clear previous data

            data.horses.forEach(horse => {
                const feedTime = horse.last_feed_time 
                    ? timeAgo(new Date(horse.last_feed_time)) 
                    : 'Never';

                // Determine button style based on feeder type
                const isManual = horse.feeder_type === 'manual';
                const buttonClasses = isManual 
                    ? 'feed-now-btn bg-green-600 hover:bg-green-700 px-4 py-2 text-white rounded-lg'
                    : 'feed-now-btn bg-gray-400 cursor-not-allowed px-4 py-2 text-white rounded-lg';
                const buttonDisabled = isManual 
                    ? `onclick="showFeedConfirmation('${horse.name}', ${horse.id})"`
                    : 'disabled';

              container.innerHTML += `
    <div class="horse-feed-item p-4 flex items-center justify-between">
        <div class="flex items-center">
            <img class="h-12 w-12 rounded-full bg-gray-100 object-cover" src="${horse.image}" alt="${horse.name}">
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">
                    ${horse.name}
                    <span class="ml-2 text-sm text-gray-400">#${horse.id}</span>
                </h3>
                <p class="text-sm text-gray-500">Feeder: <strong>${horse.feeder_type}</strong></p>
                <div class="flex items-center mt-1">
                    <span class="feed-counter inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-utensils mr-1 text-green-600"></i>
                        <span class="feed-count">${horse.feed_count}</span> feeds today
                    </span>
                    <span class="ml-2 text-sm text-gray-500">Last feed: ${feedTime}</span>
                </div>
            </div>
        </div>
        <button class="${buttonClasses}" ${buttonDisabled}>
            Feed Now
        </button>
    </div>`;

            });
        });
}


// Optional: Format last feed time
function timeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60) return `${seconds} seconds ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} minutes ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} hours ago`;
    const days = Math.floor(hours / 24);
    return `${days} days ago`;
}

// Call it when feed view is shown
// Example: when switching tabs or clicking a nav item
document.getElementById('feed-view').classList.remove('hidden');

</script>

        </main>
    </div>

    <!-- Fullscreen Camera Modal -->
    <div id="camera-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg overflow-hidden max-w-5xl w-full mx-4">
            <div class="p-4 bg-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold" id="camera-modal-title">Horse Name</h2>
                <button onclick="closeModal('camera-modal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
          <div class="relative" style="padding-bottom: 56.25%;">
    <!-- Images will be injected here -->
</div>
            <div class="p-4 bg-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="bg-green-500 text-white text-sm font-medium px-2 py-1 rounded-full">Live</span>
                        <span class="ml-2 text-gray-600">Last movement: 2 minutes ago</span>
                    </div>
                    <div>
                        <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <button class="px-3 py-1 bg-gray-200 rounded-lg">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feed Confirmation Modal -->
    <div id="feed-confirmation-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg overflow-hidden max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-utensils text-2xl text-green-600"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-center mb-2">Feed <span id="feed-horse-name">Horse</span>?</h3>
                <p class="text-gray-600 text-center mb-6">Are you sure you want to send a feed request for <span
                        id="feed-horse-name-2">Horse</span>?</p>
                        <input type="number" id="feed-weight" placeholder="Enter feed weight (kg)" 
       class="mt-2 w-full border rounded px-3 py-2" min="1">
                <div class="flex justify-center space-x-4">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        onclick="closeModal('feed-confirmation-modal')">
                        Cancel
                    </button>
                    <button class="btn-primary px-4 py-2 text-white rounded-lg" onclick="feedHorse()">
                        Confirm Feed
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Feeder Modal -->
   <!-- Add Feeder Modal -->
<div id="add-feeder-modal"
    class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg overflow-hidden max-w-md w-full mx-4">
        <div class="p-4 bg-gray-100 flex justify-between items-center">
            <h2 class="text-xl font-bold">Add New Feeder</h2>
            <button onclick="closeModal('add-feeder-modal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="feeder-form">
                <div class="mb-4">
                    <label for="feeder-name" class="block text-gray-700 text-sm font-medium mb-2">Feeder Name</label>
                    <input type="text" id="feeder-name" name="name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="e.g. Stable East Feeder">
                </div>
                <div class="mb-4">
                    <label for="feeder-location" class="block text-gray-700 text-sm font-medium mb-2">Location</label>
                    <input type="text" id="feeder-location" name="location"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="e.g. East Stable, Box 3">
                </div>
                <div class="mb-4">
                    <label for="feeder-type" class="block text-gray-700 text-sm font-medium mb-2">Feeder Type</label>
                    <select id="feeder-type" name="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        onchange="toggleScheduleInputs()">
                        <option value="manual">Manual</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>

                <!-- Schedule Times -->
                <div id="schedule-times" class="hidden">
                    <div class="mb-2">
                        <label for="morning-time" class="block text-sm text-gray-700 mb-1">Morning Time</label>
                        <input type="time" id="morning-time" name="morning"
                            class="w-full px-4 py-2 border rounded-lg border-gray-300">
                    </div>
                    <div class="mb-2">
                        <label for="day-time" class="block text-sm text-gray-700 mb-1">Day Time</label>
                        <input type="time" id="day-time" name="day"
                            class="w-full px-4 py-2 border rounded-lg border-gray-300">
                    </div>
                    <div class="mb-4">
                        <label for="night-time" class="block text-sm text-gray-700 mb-1">Night Time</label>
                        <input type="time" id="night-time" name="night"
                            class="w-full px-4 py-2 border rounded-lg border-gray-300">
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        onclick="closeModal('add-feeder-modal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary px-4 py-2 text-white rounded-lg">
                        Add Feeder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleScheduleInputs() {
    const feederType = document.getElementById('feeder-type').value;
    const scheduleFields = document.getElementById('schedule-times');
    scheduleFields.classList.toggle('hidden', feederType !== 'scheduled');
}


document.getElementById('feeder-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('save_feeder.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Feeder added!');
            closeModal('add-feeder-modal');
            location.reload();
        } else {
            alert('❌ Error: ' + data.error);
        }
    })
    .catch(err => alert('❌ Request failed'));
});
</script>


    <!-- Add Horse Modal -->
    <div id="add-horse-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg overflow-hidden max-w-md w-full mx-4">
            <div class="p-4 bg-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold">Add New Horse</h2>
                <button onclick="closeModal('add-horse-modal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="add-horse-form" method="POST" action="add_horse.php" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="horse-name" class="block text-gray-700 text-sm font-medium mb-2">Horse Name</label>
                        <input type="text" id="horse-name" name="horse_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="e.g. Thunder">
                    </div>
                    <div class="mb-4">
                        <label for="horse-age" class="block text-gray-700 text-sm font-medium mb-2">Age</label>
                        <input type="number" id="horse-age" name="horse_age"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="e.g. 7">
                    </div>
                    <div class="mb-4">
                        <label for="horse-breed" class="block text-gray-700 text-sm font-medium mb-2">Breed</label>
                        <input type="text" id="horse-breed" name="horse_breed"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="e.g. Arabian">
                    </div>
                    <div class="mb-4">
                        <label for="stable-location" class="block text-gray-700 text-sm font-medium mb-2">Stable
                            Location</label>
                        <input type="text" id="stable-location"  name="stable_location"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="e.g. North Stable, Box 5">
                    </div>
                    <div class="mb-4">
                        <label for="assigned-feeder" class="block text-gray-700 text-sm font-medium mb-2">Assigned
                            Feeder</label>
                    <select id="assigned-feeder" name="feeder_id"
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
    <option value="">Select a feeder</option>
    <?php  $feeders = $conn->query("SELECT id, name FROM feeders WHERE user_id = $user_id"); 
    while($row = $feeders->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
    <?php endwhile; ?>
</select>
                    </div>
                    <div class="mb-4">
                        <label for="feeding-schedule" class="block text-gray-700 text-sm font-medium mb-2">Feeding
                            Amount</label>
                        <select id="feeding-schedule" name="feeding_schedule"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="1kg">1Kg</option>
                            <option value="2kg">2Kg</option>
                            <option value="3kg">3Kg</option>
                            <option value="4kg">4Kg</option>
                            <option value="5kg">5Kg</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Upload Horse Photo</label>
                        <div
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload"
                                        class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                        <span>Upload a file</span>
                                        <input id="file-upload"  name="horse_image" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PNG, JPG, GIF up to 10MB
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                            onclick="closeModal('add-horse-modal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary px-4 py-2 text-white rounded-lg"
                            onclick="closeModal('add-horse-modal')">
                            Add Horse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Horse Modal -->
    <div id="edit-horse-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg overflow-hidden max-w-md w-full mx-4">
            <div class="p-4 bg-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold">Edit Horse</h2>
                <button onclick="closeModal('edit-horse-modal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
         <div class="mb-4">
    <label for="select-horse" class="block text-gray-700 text-sm font-medium mb-2">Select Horse</label>
 <select id="select-horse"
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
    <option value="">Select a horse</option>
    <?php while ($horseRow = $horseResult->fetch_assoc()): ?>
        <option value="<?= $horseRow['id'] ?>"><?= htmlspecialchars($horseRow['name']) ?></option>
    <?php endwhile; ?>
</select>

</div>
                <form>
                    <div class="mb-4">
                        <label for="edit-horse-name" class="block text-gray-700 text-sm font-medium mb-2">Horse
                            Name</label>
                        <input type="text" id="edit-horse-name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="Thunder">
                    </div>
                    <div class="mb-4">
                        <label for="edit-feeding-schedule" class="block text-gray-700 text-sm font-medium mb-2">Feeding
                            Amount</label>
                        <select id="edit-feeding-schedule"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                          
                     <option value="1kg">1Kg</option>
                            <option value="2kg">2Kg</option>
                            <option value="3kg">3Kg</option>
                            <option value="4kg">4Kg</option>
                            <option value="5kg">5Kg</option>
                        </select>
                    </div>
               <div class="mb-4">
    <label for="edit-assigned-feeder" class="block text-gray-700 text-sm font-medium mb-2">Assigned Feeder</label>
    <select id="edit-assigned-feeder"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        <?php
        $feeders = $conn->query("SELECT id, name FROM feeders WHERE user_id = $user_id");
        while ($row = $feeders->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['name']}</option>";
        }
        ?>
    </select>
</div>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                            onclick="closeModal('edit-horse-modal')">
                            Cancel
                        </button>
                        <button type="button" class="btn-primary px-4 py-2 text-white rounded-lg"
                            onclick="closeModal('edit-horse-modal')">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
document.getElementById('select-horse').addEventListener('change', async function () {
    const horseId = this.value;

    const response = await fetch(`get_horse.php?horse_id=${horseId}`);
    const data = await response.json();

    document.getElementById('edit-horse-name').value = data.name;
    document.getElementById('edit-feeding-schedule').value = data.feeding_amount;
    document.getElementById('edit-assigned-feeder').value = data.feeder_id;
});

document.querySelector('#edit-horse-modal button.btn-primary').addEventListener('click', async function () {
    const horseId = document.getElementById('select-horse').value;
    const name = document.getElementById('edit-horse-name').value;
    const feedingAmount = document.getElementById('edit-feeding-schedule').value;
    const feederId = document.getElementById('edit-assigned-feeder').value;

    const response = await fetch('update_horse.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ horse_id: horseId, horse_name: name, feeding_amount: feedingAmount, feeder_id: feederId })
    });

    const result = await response.json();
    if (result.success) {
        alert('Horse updated successfully!');
        closeModal('edit-horse-modal');
        location.reload(); // Refresh list
    } else {
        alert('Error: ' + result.error);
    }
});
</script>

    <script>
        // Switch between See and Feed views
        function switchView(viewId) {
            const seeView = document.getElementById('see-view');
            const feedView = document.getElementById('feed-view');
            const seeButton = document.getElementById('see-button');
            const feedButton = document.getElementById('feed-button');

            if (viewId === 'see-view') {
                seeView.classList.remove('hidden');
                feedView.classList.add('hidden');
                seeButton.classList.add('btn-primary');
                seeButton.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
                feedButton.classList.remove('btn-primary');
                feedButton.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
            } else {
                seeView.classList.add('hidden');
                feedView.classList.remove('hidden');
                feedButton.classList.add('btn-primary');
                feedButton.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
                seeButton.classList.remove('btn-primary');
                seeButton.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
           loadFeedView();
            }
        }

        // Open camera modal
        document.querySelectorAll('.horse-card').forEach(card => {
            card.addEventListener('click', function () {
                const horseName = this.querySelector('h3').innerText;
                document.getElementById('camera-modal-title').innerText = horseName;
                showModal('camera-modal');
            });
        });

        // Show modal
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Show feed confirmation
        function showFeedConfirmation(horseName) {
            document.getElementById('feed-horse-name').innerText = horseName;
            document.getElementById('feed-horse-name-2').innerText = horseName;
            showModal('feed-confirmation-modal');
        }
//////////////////////////////////////////////////
let selectedHorseId = null;
let selectedHorseName = null;

// ✅ Connect to MQTT (WebSocket port 9001 must be enabled on your broker)
const mqttClient = mqtt.connect("ws://51.21.200.222:9001");

mqttClient.on("connect", () => {
    console.log("✅ Connected to MQTT WebSocket broker");
});

mqttClient.on("error", err => {
    console.error("❌ MQTT connection error", err);
});

// ✅ Open Feed Confirmation Modal
function showFeedConfirmation(horseName, horseId) {
    selectedHorseId = horseId;
    selectedHorseName = horseName;

    document.getElementById('feed-horse-name').textContent = horseName;
    document.getElementById('feed-horse-name-2').textContent = horseName;
    document.getElementById('feed-confirmation-modal').classList.remove('hidden');
}

        // Feed horse
       function feedHorse() {
    const weight = document.getElementById('feed-weight').value;
    if (!weight || weight <= 0) {
        alert("Please enter a valid feed weight.");
        return;
    }

    const payload = {
        action: "feed",
        horse_id: selectedHorseId,
        horse_name: selectedHorseName,
        weight: weight,
        timestamp: new Date().toISOString()
    };
  const YOUR_USER_ID = "<?php echo $_SESSION['email']; ?>";
    const topic = `feed/${YOUR_USER_ID}`; // Replace with actual userId from session or server

    mqttClient.publish(topic, JSON.stringify(payload), (err) => {
        if (err) {
            console.error("❌ Failed to publish MQTT message:", err);
        } else {
            console.log("✅ Feed message sent:", payload);
            alert("Feed request sent!");
            closeModal('feed-confirmation-modal');
            loadFeedView(); // Refresh counts
        }
    });
}

// ✅ Close modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}
    </script>
</body>

</html>