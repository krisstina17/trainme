<?php
require_once '../includes/config.php';
require_once '../db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireTrainer();

$user = getCurrentUser();
$programId = intval($_GET['id'] ?? 0);
$program = null;
$vaje = [];

if ($programId > 0) {
    // Edit existing program
    $stmt = $pdo->prepare("SELECT * FROM programi WHERE id_program = ? AND tk_trener = ?");
    $stmt->execute([$programId, $user['id_uporabnik']]);
    $program = $stmt->fetch();
    
    if (!$program) {
        header('Location: /trainer/dashboard.php');
        exit;
    }
    
    // Get exercises
    $stmt = $pdo->prepare("SELECT * FROM vaje WHERE tk_program = ? ORDER BY zaporedje ASC");
    $stmt->execute([$programId]);
    $vaje = $stmt->fetchAll();
}

$errors = [];
$success = false;

// Debug: Log POST data (remove in production)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET['debug'])) {
    error_log("POST vaje data: " . print_r($_POST['vaje'] ?? 'NOT SET', true));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $naziv = trim($_POST['naziv'] ?? '');
    $opis = trim($_POST['opis'] ?? '');
    $cena = floatval($_POST['cena'] ?? 0);
    $trajanje_dni = intval($_POST['trajanje_dni'] ?? 0);
    
    if (empty($naziv) || empty($opis) || $cena <= 0 || $trajanje_dni <= 0) {
        $errors[] = "Vsa polja so obvezna in morajo biti veljavna.";
    } else {
        if ($programId > 0) {
            // Update
            try {
                // First check if program exists and belongs to trainer
                $checkStmt = $pdo->prepare("SELECT id_program FROM programi WHERE id_program = ? AND tk_trener = ?");
                $checkStmt->execute([$programId, $user['id_uporabnik']]);
                if (!$checkStmt->fetch()) {
                    $errors[] = "Program ne obstaja ali nimate dovoljenja za urejanje.";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE programi 
                        SET naziv = ?, opis = ?, cena = ?, trajanje_dni = ?
                        WHERE id_program = ? AND tk_trener = ?
                    ");
                    $result = $stmt->execute([$naziv, $opis, $cena, $trajanje_dni, $programId, $user['id_uporabnik']]);
                    
                    if (!$result) {
                        $errors[] = "Napaka pri posodabljanju programa.";
                    }
                    // Note: rowCount() can be 0 if values didn't change, which is OK
                }
            } catch (PDOException $e) {
                $errors[] = "Napaka pri posodabljanju programa: " . $e->getMessage();
            }
        } else {
            // Insert
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO programi (tk_trener, naziv, opis, cena, trajanje_dni)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$user['id_uporabnik'], $naziv, $opis, $cena, $trajanje_dni]);
                
                if (!$result) {
                    $errors[] = "Napaka pri ustvarjanju programa.";
                } else {
                    $programId = $pdo->lastInsertId();
                }
            } catch (PDOException $e) {
                $errors[] = "Napaka pri ustvarjanju programa: " . $e->getMessage();
            }
        }
        
        // Handle exercises - only if program update/insert was successful
        if (empty($errors)) {
            if ($programId > 0) {
                // Check if exercises are submitted
                // IMPORTANT: When using name="vaje[][naziv]", PHP creates an array with numeric keys
                // But empty fields might not be sent, so we need to check carefully
                if (isset($_POST['vaje']) && is_array($_POST['vaje'])) {
                    // Validate that at least one exercise has both name and description
                    $validExercises = [];
                    $exerciseCount = 0;
                    
                    foreach ($_POST['vaje'] as $index => $vaja) {
                        $exerciseCount++;
                        // Handle array structure: vaje[][naziv] creates numeric keys
                        $naziv = isset($vaja['naziv']) ? trim($vaja['naziv']) : '';
                        $opis = isset($vaja['opis']) ? trim($vaja['opis']) : '';
                        $video_url = isset($vaja['video_url']) ? trim($vaja['video_url']) : '';
                        
                        // Debug: log what we're processing
                        if (isset($_GET['debug'])) {
                            error_log("Processing exercise $index: naziv='$naziv', opis='$opis'");
                        }
                        
                        // Only add if both naziv and opis are not empty
                        if (!empty($naziv) && !empty($opis)) {
                            $validExercises[] = [
                                'naziv' => $naziv,
                                'opis' => $opis,
                                'video_url' => $video_url
                            ];
                        }
                    }
                    
                    // Only proceed if we have at least one valid exercise
                    if (empty($validExercises)) {
                        if ($exerciseCount > 0) {
                            $errors[] = "Vsaj ena vaja mora imeti izpolnjen naziv in opis. Preverite, da sta oba polja izpolnjena in nista prazna.";
                        } else {
                            // No exercises in POST - check if program has existing exercises
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vaje WHERE tk_program = ?");
                            $stmt->execute([$programId]);
                            $exerciseCount = $stmt->fetch()['count'];
                            
                            if ($exerciseCount == 0) {
                                $errors[] = "Program mora imeti vsaj eno vajo.";
                            }
                        }
                    } else {
                        // Delete existing exercises
                        $stmt = $pdo->prepare("DELETE FROM vaje WHERE tk_program = ?");
                        $stmt->execute([$programId]);
                        
                        // Insert new exercises
                        foreach ($validExercises as $index => $vaja) {
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO vaje (tk_program, naziv, opis, video_url, zaporedje)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $programId,
                                    $vaja['naziv'],
                                    $vaja['opis'],
                                    $vaja['video_url'],
                                    $index + 1
                                ]);
                            } catch (PDOException $e) {
                                $errors[] = "Napaka pri shranjevanju vaje: " . $e->getMessage();
                            }
                        }
                    }
                } else {
                    // No exercises submitted - check if program already has exercises
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vaje WHERE tk_program = ?");
                    $stmt->execute([$programId]);
                    $exerciseCount = $stmt->fetch()['count'];
                    
                    // If no exercises exist, require at least one
                    if ($exerciseCount == 0) {
                        $errors[] = "Program mora imeti vsaj eno vajo.";
                    }
                    // If exercises exist, we keep them (user didn't modify exercises section)
                }
            } else {
                // For new programs, exercises are required and must be saved
                if (!isset($_POST['vaje']) || !is_array($_POST['vaje']) || count($_POST['vaje']) == 0) {
                    $errors[] = "Program mora imeti vsaj eno vajo.";
                } else {
                    // Validate exercises for new program
                    $validExercises = [];
                    foreach ($_POST['vaje'] as $vaja) {
                        $naziv = isset($vaja['naziv']) ? trim($vaja['naziv']) : '';
                        $opis = isset($vaja['opis']) ? trim($vaja['opis']) : '';
                        $video_url = isset($vaja['video_url']) ? trim($vaja['video_url']) : '';
                        
                        if (!empty($naziv) && !empty($opis)) {
                            $validExercises[] = [
                                'naziv' => $naziv,
                                'opis' => $opis,
                                'video_url' => $video_url
                            ];
                        }
                    }
                    
                    if (empty($validExercises)) {
                        $errors[] = "Vsaj ena vaja mora imeti izpolnjen naziv in opis.";
                    } else {
                        // Insert exercises for new program
                        foreach ($validExercises as $index => $vaja) {
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO vaje (tk_program, naziv, opis, video_url, zaporedje)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $programId,
                                    $vaja['naziv'],
                                    $vaja['opis'],
                                    $vaja['video_url'],
                                    $index + 1
                                ]);
                            } catch (PDOException $e) {
                                $errors[] = "Napaka pri shranjevanju vaje: " . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }
        
        // Only redirect if there are no errors
        if (empty($errors)) {
            // Redirect to prevent form resubmission and show success message
            header('Location: /trainer/uredi-program.php?id=' . $programId . '&success=1');
            exit;
        }
    }
}

include '../header.php';
?>

<section class="edit-program-section">
    <div class="container">
        <h1 class="page-title"><?php echo $programId > 0 ? 'Uredi program' : 'Dodaj program'; ?></h1>
        
        <div class="edit-program-card">
            <?php 
            if ($success) {
                showToast('Program uspešno shranjen!', 'success');
                // Redirect to prevent refresh loop
                header('Location: /trainer/uredi-program.php?id=' . $programId . '&success=1');
                exit;
            }
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    showToast($e, 'error');
                }
            }
            // Show success message if redirected with success parameter
            if (isset($_GET['success']) && $_GET['success'] == '1') {
                showToast('Program uspešno shranjen!', 'success');
            }
            ?>

            <form method="POST" class="edit-program-form" id="programForm">
                <div class="form-group">
                    <label for="naziv">Naziv programa</label>
                    <input type="text" id="naziv" name="naziv" required
                           value="<?php echo htmlspecialchars($program['naziv'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="opis">Opis</label>
                    <textarea id="opis" name="opis" rows="5" required><?php echo htmlspecialchars($program['opis'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cena">Cena (€)</label>
                        <input type="number" id="cena" name="cena" step="0.01" min="0" required
                               value="<?php echo $program['cena'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="trajanje_dni">Trajanje (dni)</label>
                        <input type="number" id="trajanje_dni" name="trajanje_dni" min="1" required
                               value="<?php echo $program['trajanje_dni'] ?? ''; ?>">
                    </div>
                </div>

                <div class="exercises-section">
                    <h3>Vaje</h3>
                    <p class="section-hint">Dodajte vaje za vaš program. Vsaka vaja mora imeti naziv in opis.</p>
                    <div id="exercisesList">
                        <?php if (!empty($vaje)): ?>
                            <?php foreach ($vaje as $index => $vaja): ?>
                                <div class="exercise-input-group" data-exercise-index="<?php echo $index; ?>">
                                    <input type="text" name="vaje[<?php echo $index; ?>][naziv]" placeholder="Naziv vaje" required
                                           value="<?php echo htmlspecialchars($vaja['naziv']); ?>">
                                    <textarea name="vaje[<?php echo $index; ?>][opis]" placeholder="Opis vaje" required><?php echo htmlspecialchars($vaja['opis']); ?></textarea>
                                    <input type="url" name="vaje[<?php echo $index; ?>][video_url]" placeholder="Video URL (opcijsko)"
                                           value="<?php echo htmlspecialchars($vaja['video_url'] ?? ''); ?>">
                                    <button type="button" class="btn btn-sm btn-danger remove-exercise">Odstrani</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="exercise-input-group" data-exercise-index="0">
                                <input type="text" name="vaje[0][naziv]" placeholder="Naziv vaje" required>
                                <textarea name="vaje[0][opis]" placeholder="Opis vaje" required></textarea>
                                <input type="url" name="vaje[0][video_url]" placeholder="Video URL (opcijsko)">
                                <button type="button" class="btn btn-sm btn-danger remove-exercise">Odstrani</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" id="addExercise">+ Dodaj vajo</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Shrani program</button>
                    <a href="/trainer/dashboard.php" class="btn btn-secondary">Prekliči</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// Add exercise button handler
document.getElementById('addExercise').addEventListener('click', function() {
    const exercisesList = document.getElementById('exercisesList');
    const existingExercises = exercisesList.querySelectorAll('.exercise-input-group');
    
    // Get the next index
    let nextIndex = 0;
    if (existingExercises.length > 0) {
        const lastExercise = existingExercises[existingExercises.length - 1];
        const lastIndex = parseInt(lastExercise.getAttribute('data-exercise-index') || '0');
        nextIndex = lastIndex + 1;
    }
    
    const newExercise = document.createElement('div');
    newExercise.className = 'exercise-input-group';
    newExercise.setAttribute('data-exercise-index', nextIndex);
    newExercise.innerHTML = `
        <input type="text" name="vaje[${nextIndex}][naziv]" placeholder="Naziv vaje" required>
        <textarea name="vaje[${nextIndex}][opis]" placeholder="Opis vaje" required></textarea>
        <input type="url" name="vaje[${nextIndex}][video_url]" placeholder="Video URL (opcijsko)">
        <button type="button" class="btn btn-sm btn-danger remove-exercise">Odstrani</button>
    `;
    exercisesList.appendChild(newExercise);
    
    // Add remove handler to new exercise
    newExercise.querySelector('.remove-exercise').addEventListener('click', function() {
        if (document.querySelectorAll('.exercise-input-group').length > 1) {
            newExercise.remove();
            // Reindex remaining exercises
            reindexExercises();
        } else {
            alert('Program mora imeti vsaj eno vajo.');
        }
    });
});

// Function to reindex exercises after removal
function reindexExercises() {
    const exercises = document.querySelectorAll('.exercise-input-group');
    exercises.forEach((exercise, index) => {
        exercise.setAttribute('data-exercise-index', index);
        const nazivInput = exercise.querySelector('input[name*="[naziv]"]');
        const opisInput = exercise.querySelector('textarea[name*="[opis]"]');
        const videoInput = exercise.querySelector('input[name*="[video_url]"]');
        
        if (nazivInput) nazivInput.name = `vaje[${index}][naziv]`;
        if (opisInput) opisInput.name = `vaje[${index}][opis]`;
        if (videoInput) videoInput.name = `vaje[${index}][video_url]`;
    });
}

// Remove exercise button handlers for existing exercises
document.querySelectorAll('.remove-exercise').forEach(btn => {
    btn.addEventListener('click', function() {
        if (document.querySelectorAll('.exercise-input-group').length > 1) {
            this.closest('.exercise-input-group').remove();
            // Reindex remaining exercises
            reindexExercises();
        } else {
            alert('Program mora imeti vsaj eno vajo.');
        }
    });
});

// Form validation before submit
document.getElementById('programForm').addEventListener('submit', function(e) {
    const exercises = document.querySelectorAll('.exercise-input-group');
    let hasValidExercise = false;
    let emptyExercises = [];
    
    console.log('Validating exercises, count:', exercises.length);
    
    exercises.forEach((exercise, index) => {
        const nazivInput = exercise.querySelector('input[name*="[naziv]"]');
        const opisInput = exercise.querySelector('textarea[name*="[opis]"]');
        
        if (nazivInput && opisInput) {
            const naziv = nazivInput.value.trim();
            const opis = opisInput.value.trim();
            
            console.log(`Exercise ${index + 1}: naziv="${naziv}", opis="${opis}"`);
            
            if (naziv && opis) {
                hasValidExercise = true;
            } else if (naziv || opis) {
                // Partial fill - warn user
                emptyExercises.push(index + 1);
            }
        } else {
            console.error(`Exercise ${index + 1}: Missing inputs!`);
        }
    });
    
    if (!hasValidExercise) {
        e.preventDefault();
        if (emptyExercises.length > 0) {
            alert('Vaja ' + emptyExercises.join(', ') + ' nima izpolnjenega naziva ali opisa. Prosimo, izpolnite oba polja ali odstranite vajo.');
        } else {
            alert('Vsaj ena vaja mora imeti izpolnjen naziv in opis!');
        }
        return false;
    }
    
    // Debug: Log form data before submit
    const formData = new FormData(this);
    console.log('Form data:');
    const vajeData = [];
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('vaje')) {
            console.log(key + ':', value);
            // Parse the key to extract index and field
            const match = key.match(/vaje\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const index = parseInt(match[1]);
                const field = match[2];
                if (!vajeData[index]) {
                    vajeData[index] = {};
                }
                vajeData[index][field] = value;
            }
        }
    }
    console.log('Parsed vaje data:', vajeData);
    
    // Ensure all exercises have both naziv and opis
    const invalidExercises = vajeData.filter(ex => !ex.naziv || !ex.opis || !ex.naziv.trim() || !ex.opis.trim());
    if (invalidExercises.length > 0 && vajeData.length > 0) {
        console.error('Invalid exercises found:', invalidExercises);
    }
});
</script>

<?php include '../footer.php'; ?>

