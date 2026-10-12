<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageLandingPageController.php";

$controller = new ManageLandingPageController();
$content = $controller->getEditableContent();

$slideKeys = [];
foreach ($content['features'] as $key => $section) {
    if ($section['exists']) {
        $slideKeys[] = $key;
    }
}

$errorMessage = null;
if (isset($_SESSION['landing_error'])) {
    $errorMessage = $_SESSION['landing_error'];
    unset($_SESSION['landing_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Update Landing Page - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="system-admin-dashboard">
    <script src="../script.js"></script>

    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="dashboard-header-right">
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="systemAdminDashboardPage.php">Dashboard</a>
                    <a href="systemAdminProfilePage.php">Profile</a>
                    <a href="ManageUniversityPage.php">Manage Universities</a>
                    <a href="manageLandingPage.php">Manage Landing Page</a>
                    <a href="UpdateAIModelPage.php">Update AI Model</a>
                    <a href="SystemOperationPage.php">System Operations</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>
        </div>

    </header>

    <main class="landing-manage-page">

        <a href="manageLandingPage.php" class="admin-edit-back">&#8249; Back</a>

        <?php if ($errorMessage): ?>
            <div class="error-message admin-edit-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="landing-manage-bar landing-manage-bar-right">
            <button type="button" id="saveBtn" class="landing-save-btn" onclick="openSaveConfirm()" disabled>Save Changes</button>
        </div>

        <form id="updateLandingForm" action="../controller/manageLandingPageController.php" method="POST" hidden>
            <input type="hidden" name="update_landing" value="1">

            <textarea name="heroTitle"><?php echo htmlspecialchars($content['hero']['title']); ?></textarea>
            <textarea name="heroSubtitle"><?php echo htmlspecialchars($content['hero']['subtitle']); ?></textarea>

            <?php foreach ($content['features'] as $key => $section): ?>
                <textarea name="features[<?php echo $key; ?>][title]"><?php echo htmlspecialchars($section['title']); ?></textarea>

                <?php foreach ($section['boxes'] as $i => $box): ?>
                    <textarea name="features[<?php echo $key; ?>][boxes][<?php echo $i; ?>][title]"><?php echo htmlspecialchars($box['title'] ?? ''); ?></textarea>
                    <textarea name="features[<?php echo $key; ?>][boxes][<?php echo $i; ?>][desc]"><?php echo htmlspecialchars($box['desc'] ?? ''); ?></textarea>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </form>

        <div class="landing-preview-frame editing">
            <iframe id="landingEditor" src="viewLandingPage.php" title="Landing page editor" scrolling="no"></iframe>
        </div>

    </main>

    <!-- Confirm pop-up -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-box modal-large">
            <button type="button" class="modal-close" onclick="closeModal('confirmModal')">&times;</button>

            <p class="modal-title">Are you sure you want to update the landing page content?</p>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('confirmModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-approve"
                    onclick="document.getElementById('updateLandingForm').submit();">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Validation notice pop-up -->
    <div id="noticeModal" class="modal-overlay">
        <div class="modal-box modal-flash modal-large">
            <button type="button" class="modal-close" onclick="closeModal('noticeModal')">&times;</button>
            <p class="modal-title" id="noticeText"></p>
        </div>
    </div>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });

        // Feature section keys in the same order as the slides on the landing page
        var slideKeys = <?php echo json_encode($slideKeys); ?>;

        var form = document.getElementById('updateLandingForm');
        var frame = document.getElementById('landingEditor');
        var saveBtn = document.getElementById('saveBtn');
        var bindings = [];

        // Remove extra spaces / blank lines so "Hello \n\n World" and "Hello\nWorld" compare equal
        function normalise(text) {
            return text.replace(/\r/g, '').split('\n')
                .map(function (line) { return line.trim(); })
                .filter(function (line) { return line !== ''; })
                .join('\n');
        }

        function currentValue(binding) {
            return normalise(binding.el.innerText);
        }

        function fitFrame() {
            try {
                frame.style.height = frame.contentWindow.document.documentElement.scrollHeight + 'px';
            } catch (e) { }
        }

        function refreshState() {
            var changed = bindings.some(function (b) {
                return currentValue(b) !== b.original;
            });
            saveBtn.disabled = !changed;
            fitFrame();
        }

        function makeEditable(doc, el, fieldName, options) {
            var field = form.elements[fieldName];
            if (!el || !field) { return; }

            el.contentEditable = 'true';
            el.spellcheck = false;
            el.setAttribute('data-editable', '1');

            // One-line fields: Enter must not create a new line
            el.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !options.multiline) {
                    event.preventDefault();
                }
            });

            // Paste as plain text only (no bold, links, colours from other sites)
            el.addEventListener('paste', function (event) {
                event.preventDefault();
                var text = (event.clipboardData || window.clipboardData).getData('text/plain');
                if (!options.multiline) {
                    text = text.replace(/\s*\n\s*/g, ' ');
                }
                doc.execCommand('insertText', false, text);
            });

            el.addEventListener('input', refreshState);

            bindings.push({
                el: el,
                field: field,
                max: options.max,
                label: options.label,
                original: normalise(field.defaultValue)
            });
        }

        frame.addEventListener('load', function () {
            var doc = frame.contentDocument;

            // Dashed outline so the admin can see what is editable
            var style = doc.createElement('style');
            style.textContent =
                '[data-editable]{outline:2px dashed #2E6DD6;outline-offset:4px;cursor:text;}' +
                '[data-editable]:hover{background:rgba(46,109,214,.12);}' +
                '[data-editable]:focus{outline:2px solid #2E6DD6;background:rgba(46,109,214,.18);}';
            doc.head.appendChild(style);

            doc.addEventListener('click', function (event) {
                var link = event.target.closest('a');
                if (link) { event.preventDefault(); }
            }, true);

            var SELECTORS = {
                heroTitle: '.hero-left h1',
                heroSubtitle: '.hero-right p',
                slide: '.role-slide',
                slideHeading: '.features-title',
                box: '.feature-box',
                boxTitle: 'h3',
                boxDesc: 'p'
            };

            makeEditable(doc, doc.querySelector(SELECTORS.heroTitle), 'heroTitle',
                { multiline: true, max: 200, label: 'The hero title' });

            makeEditable(doc, doc.querySelector(SELECTORS.heroSubtitle), 'heroSubtitle',
                { max: 500, label: 'The hero subtitle' });

            doc.querySelectorAll(SELECTORS.slide).forEach(function (slide, i) {
                var key = slideKeys[i];
                if (!key) { return; }

                makeEditable(doc, slide.querySelector(SELECTORS.slideHeading),
                    'features[' + key + '][title]',
                    { max: 500, label: 'A features heading' });

                slide.querySelectorAll(SELECTORS.box).forEach(function (box, j) {
                    var base = 'features[' + key + '][boxes][' + j + ']';
                    makeEditable(doc, box.querySelector(SELECTORS.boxTitle), base + '[title]',
                        { max: 100, label: 'A feature title' });
                    makeEditable(doc, box.querySelector(SELECTORS.boxDesc), base + '[desc]',
                        { max: 300, label: 'A feature description' });
                });
            });

            fitFrame();
            setTimeout(fitFrame, 500);
            setTimeout(fitFrame, 1500);
        });

        window.addEventListener('resize', fitFrame);

        function showNotice(message) {
            document.getElementById('noticeText').textContent = message;
            openModal('noticeModal');
        }

        function openSaveConfirm() {
            for (var i = 0; i < bindings.length; i++) {
                var value = currentValue(bindings[i]);

                if (value === '') {
                    showNotice(bindings[i].label + ' cannot be empty.');
                    return;
                }
                if (value.length > bindings[i].max) {
                    showNotice(bindings[i].label + ' is too long (maximum ' + bindings[i].max + ' characters).');
                    return;
                }
            }

            bindings.forEach(function (b) {
                b.field.value = currentValue(b);
            });

            openModal('confirmModal');
        }
    </script>

</body>

</html>