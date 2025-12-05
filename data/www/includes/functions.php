<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Extract YouTube video ID from URL
 */
function getYouTubeVideoId($url) {
    if (empty($url)) return null;
    
    // Handle different YouTube URL formats
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Generate QR code data URL (using API)
 */
function generateQRCode($data) {
    $size = 200;
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    return $url;
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Calculate days remaining
 */
function daysRemaining($endDate) {
    $end = new DateTime($endDate);
    $now = new DateTime();
    $diff = $now->diff($end);
    return $diff->invert ? 0 : $diff->days;
}

/**
 * Get trainer average rating
 */
function getTrainerRating($trainerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT AVG(ocena) as avg_rating, COUNT(*) as count
        FROM ocene
        WHERE tk_trener = ?
    ");
    $stmt->execute([$trainerId]);
    $result = $stmt->fetch();
    
    $avgRating = $result['avg_rating'] ?? null;
    $count = $result['count'] ?? 0;
    
    return [
        'average' => $avgRating !== null ? round((float)$avgRating, 1) : 0.0,
        'count' => (int)$count
    ];
}

/**
 * Upload profile image
 */
function uploadProfileImage($file, $userId) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_DIR . $filename;
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'error' => 'Upload failed'];
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    // Simple mail function - can be replaced with PHPMailer
    $headers = "MIME-Version: 1.0\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Get exercise image URL (fallback when no video)
 */
function getExerciseImageUrl($exerciseName) {
    // Use Unsplash API to get relevant exercise images
    $searchTerm = urlencode($exerciseName . ' workout exercise');
    // Using Unsplash Source API for consistent images
    $seed = crc32($exerciseName); // Deterministic seed for same exercise = same image
    return "https://source.unsplash.com/800x450/?fitness,workout,{$searchTerm}&sig={$seed}";
}

/**
 * Get program image URL based on program name/type
 * Uses high-quality, modern fitness images
 */
function getProgramImageUrl($programName) {
    $name = strtolower($programName);
    
    // High-quality, modern fitness images from Unsplash
    $imageMap = [
        // Pilates - elegant, feminine
        'pilates' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?w=800&h=500&fit=crop&q=80',
        'pilates flow' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?w=800&h=500&fit=crop&q=80',
        'pilates power' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?w=800&h=500&fit=crop&q=80',
        'večernji pilates' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?w=800&h=500&fit=crop&q=80',
        
        // HIIT / Cardio - energetic, dynamic
        'hiit' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'cardio' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'cardio blast' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'hiit trening' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        
        // Yoga - peaceful, graceful
        'yoga' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'yoga balance' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'yoga flow' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'jutranja yoga' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        
        // Booty & Core - strong, feminine
        'booty' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'core' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'core strength' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'moč jedra' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        
        // Morning / Energy - fresh, vibrant
        'morning' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'morning energy' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'jutranja energija' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        
        // Evening / Relax - calm, soothing
        'evening' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'evening relax' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'relax' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'večernji' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        
        // Strength - powerful, strong
        'strong' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'strength' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'advanced strength' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'moč' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        
        // Flexibility - graceful, stretching
        'flexibility' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'flexibility flow' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'gibljivost' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        
        // Total Body - comprehensive, full workout
        'total body' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        'total body tone' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        'full body' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        'full body challenge' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        'celotno telo' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        
        // Quick Fit - fast, efficient
        'quick fit' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        'quick' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        'hitro' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        
        // Legs & Glutes - lower body focus
        'legs' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'legs & glutes' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'noge' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        
        // Upper Body - upper body strength
        'upper body' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'upper body power' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'zgornji del telesa' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        
        // Weight Loss - energetic, dynamic
        'weight loss' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        'weight loss express' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        'hujšanje' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        
        // Beginner - gentle, approachable
        'beginner' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'beginner friendly' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'začetnike' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'za začetnike' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        
        // Postnatal - gentle recovery
        'postnatal' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        'postnatal recovery' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=500&fit=crop&q=80',
        
        // Senior - gentle, safe
        'senior' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
        'senior fitness' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=500&fit=crop&q=80',
    ];
    
    // Check for exact matches first
    foreach ($imageMap as $key => $url) {
        if (stripos($name, $key) !== false) {
            return $url;
        }
    }
    
    // Fallback: use program name hash for consistent, high-quality image
    $seed = crc32($programName);
    $images = [
        'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=500&fit=crop&q=80',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=500&fit=crop&q=80',
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop&q=80',
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=500&fit=crop&q=80',
        'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?w=800&h=500&fit=crop&q=80',
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=400&fit=crop',
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=400&fit=crop',
    ];
    
    return $images[abs($seed) % count($images)];
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

/**
 * Get nearby fitness centers using Google Places API
 */
function getNearbyFitnessCenters($lat, $lng, $radius = 25, $limit = 5) {
    $centers = [];
    
    // Try Overpass API first
    $overpassUrl = "https://overpass-api.de/api/interpreter";
    
    $query = "[out:json][timeout:25];
(
  node[\"leisure\"=\"fitness_centre\"](around:{$radius},{$lat},{$lng});
  node[\"amenity\"=\"gym\"](around:{$radius},{$lat},{$lng});
  node[\"sport\"=\"fitness\"](around:{$radius},{$lat},{$lng});
  node[\"leisure\"=\"sports_centre\"](around:{$radius},{$lat},{$lng});
  way[\"leisure\"=\"fitness_centre\"](around:{$radius},{$lat},{$lng});
  way[\"amenity\"=\"gym\"](around:{$radius},{$lat},{$lng});
  way[\"leisure\"=\"sports_centre\"](around:{$radius},{$lat},{$lng});
);
out center meta;";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $overpassUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TrainMe/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    unset($ch);
    
    if ($httpCode === 200 && $response && empty($curlError)) {
        $data = json_decode($response, true);
        
        if (isset($data['elements']) && !empty($data['elements'])) {
            foreach ($data['elements'] as $element) {
                $centerLat = $element['lat'] ?? ($element['center']['lat'] ?? null);
                $centerLng = $element['lon'] ?? ($element['center']['lon'] ?? null);
                
                if ($centerLat && $centerLng) {
                    $name = $element['tags']['name'] ?? 
                           ($element['tags']['name:sl'] ?? 
                           ($element['tags']['name:en'] ?? 
                           ($element['tags']['name:de'] ?? 'Fitnes center')));
                    
                    $address = $element['tags']['addr:street'] ?? '';
                    if (!empty($element['tags']['addr:housenumber'])) {
                        $address .= ' ' . $element['tags']['addr:housenumber'];
                    }
                    if (!empty($element['tags']['addr:city'])) {
                        $address .= ', ' . $element['tags']['addr:city'];
                    }
                    if (empty($address)) {
                        $address = 'Slovenija';
                    }
                    
                    $distance = calculateDistance($lat, $lng, $centerLat, $centerLng);
                    
                    $centers[] = [
                        'name' => $name,
                        'address' => $address,
                        'lat' => (float)$centerLat,
                        'lng' => (float)$centerLng,
                        'distance' => round($distance, 2)
                    ];
                }
            }
            
            // Sort by distance
            usort($centers, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            
            // Return only the closest ones
            $centers = array_slice($centers, 0, $limit);
            
            if (!empty($centers)) {
                return $centers;
            }
        }
    }
    
    // Fallback to hardcoded fitness centers for Slovenia
    $slovenianGyms = [
        [
            'name' => 'Fitnes Center Ljubljana',
            'address' => 'Trubarjeva cesta 1, Ljubljana',
            'lat' => 46.0569,
            'lng' => 14.5058,
            'distance' => calculateDistance($lat, $lng, 46.0569, 14.5058)
        ],
        [
            'name' => 'Gym Maribor',
            'address' => 'Glavni trg 5, Maribor',
            'lat' => 46.5547,
            'lng' => 15.6459,
            'distance' => calculateDistance($lat, $lng, 46.5547, 15.6459)
        ],
        [
            'name' => 'CrossFit Celje',
            'address' => 'Prešernova ulica 10, Celje',
            'lat' => 46.2309,
            'lng' => 15.2604,
            'distance' => calculateDistance($lat, $lng, 46.2309, 15.2604)
        ],
        [
            'name' => 'Fitnes Center Kranj',
            'address' => 'Pohorska ulica 10, Kranj',
            'lat' => 46.2389,
            'lng' => 14.3556,
            'distance' => calculateDistance($lat, $lng, 46.2389, 14.3556)
        ],
        [
            'name' => 'Yoga Studio Koper',
            'address' => 'Kidričeva ulica 5, Koper',
            'lat' => 45.5481,
            'lng' => 13.7302,
            'distance' => calculateDistance($lat, $lng, 45.5481, 13.7302)
        ],
        [
            'name' => 'Fitnes Center Novo Mesto',
            'address' => 'Glavni trg 1, Novo Mesto',
            'lat' => 45.8044,
            'lng' => 15.1689,
            'distance' => calculateDistance($lat, $lng, 45.8044, 15.1689)
        ],
        [
            'name' => 'Gym Ptuj',
            'address' => 'Mestni trg 1, Ptuj',
            'lat' => 46.4197,
            'lng' => 15.8697,
            'distance' => calculateDistance($lat, $lng, 46.4197, 15.8697)
        ]
    ];
    
    // Sort fallback by distance
    usort($slovenianGyms, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });
    
    return array_slice($slovenianGyms, 0, $limit);
}
