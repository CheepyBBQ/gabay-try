<?php
// ---------------------------------------------------------------------------
// Crisis language detection. Edit the two lists below to add phrases.
// Phrases are written in plain lowercase letters without apostrophes ("dont", not "don't").
// A match flags the post for counselors and shows the student the crisis pop-up.
// It is keyword matching, not a diagnosis: it misses things and flags harmless things.
// ---------------------------------------------------------------------------
const CRISIS_PHRASES = [
    // English
    'kill myself', 'killing myself', 'kill my self', 'end my life', 'end it all', 'end everything', 'take my life',
    'want to die', 'wanna die', 'wish i was dead', 'wish i were dead', 'wish i was never born', 'wish i were never born',
    'suicide', 'suicidal', 'self harm', 'selfharm', 'hurt myself', 'harm myself', 'cut myself', 'cutting myself',
    'hang myself', 'slit my wrists', 'overdose on', 'jump off a bridge', 'jump off a building', 'jump off the roof',
    'no reason to live', 'nothing to live for', 'not worth living', 'life is not worth', 'better off dead',
    'better off without me', 'rather be dead', 'tired of living', 'tired of being alive', 'give up on life',
    'dont want to live', 'dont want to be here', 'dont want to wake up', 'dont wanna live', 'dont wanna be here',
    'cant go on', 'cant take it anymore', 'cant do this anymore', 'no way out', 'disappear forever',
    // Filipino / Taglish
    'magpakamatay', 'magpapakamatay', 'magpakamatay na lang ako', 'mamatay na lang ako', 'mamatay na ako',
    'gusto ko nang mamatay', 'gusto ko na mamatay', 'gusto kong mamatay', 'gusto ko ng mamatay',
    'ayoko na', 'ayoko nang', 'ayaw ko na', 'ayaw ko nang', 'ayoko nang mabuhay', 'ayaw ko nang mabuhay',
    'sawa na ako sa buhay', 'sawa na akong mabuhay', 'pagod na ako sa buhay', 'pagod na pagod na ako',
    'tapusin ko na', 'tapusin ko na ang buhay ko', 'wakasan ko na', 'wakasan ang buhay', 'patayin ang sarili',
    'saktan ang sarili', 'sasaktan ko ang sarili ko', 'wala nang saysay', 'walang saysay ang buhay',
    'walang halaga ang buhay ko', 'wala nang pag asa', 'mawala na lang ako', 'mas mabuti pang mawala na lang ako',
    'hindi ko na kaya', 'di ko na kaya', 'hindi na ako okay', 'kamatayan',
    // Cebuano (a few)
    'gusto na ko mamatay', 'gusto nako mamatay', 'ayaw na nako', 'wala nay pulos',
];
// Short phrases are only matched with word boundaries, so they do not fire inside other words.
// Phrases at least 8 letters long (and these) are also matched with spaces removed, which catches "k i l l  m y s e l f".
const CRISIS_ALWAYS_SQUASH = ['suicide'];

// Lowercase, strip invisible characters and accents, swap look-alike letters, drop apostrophes.
function crisis_clean(string $text): string {
    $t = mb_strtolower($text, 'UTF-8');
    $t = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}\x{FEFF}\x{00AD}]/u', '', $t) ?? $t;
    if (class_exists('Normalizer')) $t = Normalizer::normalize($t, Normalizer::FORM_KD) ?: $t;
    $t = preg_replace('/\p{Mn}+/u', '', $t) ?? $t;
    // Fullwidth letters and digits (ｗａｎｔ) back to plain ones, and common accented letters when the intl extension is missing.
    $t = preg_replace_callback('/[\x{FF01}-\x{FF5E}]/u', fn($m) => chr(mb_ord($m[0], 'UTF-8') - 0xFEE0), $t) ?? $t;
    $t = strtr($t, ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
                    'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ñ'=>'n','ç'=>'c','ý'=>'y','ÿ'=>'y']);
    $t = strtr($t, ['а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'у' => 'y', 'х' => 'x', 'і' => 'i',
                    'ѕ' => 's', 'ο' => 'o', 'ν' => 'v', 'α' => 'a', 'ε' => 'e', 'ı' => 'i']);
    return str_replace(["'", '’', '‘', '`'], '', $t);
}

// Letters only, one space between words, and repeated letters collapsed ("ayawww" becomes "ayaw").
// Returns [spaced, squashed].
function crisis_letters(string $s): array {
    $spaced = trim(preg_replace('/[^\p{L}]+/u', ' ', $s) ?? '');
    $spaced = preg_replace('/(\p{L})\1+/u', '$1', $spaced) ?? $spaced;
    $squashed = preg_replace('/(\p{L})\1+/u', '$1', str_replace(' ', '', $spaced)) ?? '';
    return [$spaced, $squashed];
}

function crisis_phrase_forms(): array {
    static $forms = null;
    if ($forms === null) {
        $forms = [];
        foreach (CRISIS_PHRASES as $p) {
            [$sp, $sq] = crisis_letters(crisis_clean($p));
            $forms[] = [$sp, $sq, mb_strlen($sq) >= 8 || in_array($p, CRISIS_ALWAYS_SQUASH, true)];
        }
    }
    return $forms;
}

function has_crisis_language(string $text): bool {
    $t = crisis_clean($text);
    // Number and symbol substitutions ("4yaW k0 n4", "k!ll my$elf"). "1" and "!" can mean i or l, so try each.
    $sub = ['4' => 'a', '@' => 'a', '3' => 'e', '0' => 'o', '5' => 's', '$' => 's', '7' => 't', '+' => 't'];
    $variants = [$t, strtr($t, $sub), strtr($t, $sub + ['1' => 'i', '!' => 'i', '|' => 'i']), strtr($t, $sub + ['1' => 'l', '|' => 'l'])];
    $forms = crisis_phrase_forms();
    foreach ($variants as $v) {
        [$spaced, $squashed] = crisis_letters($v);
        if ($spaced === '') continue;
        $padded = ' ' . $spaced . ' ';
        foreach ($forms as [$psp, $psq, $squash]) {
            if ($psp === '') continue;
            if (str_contains($padded, ' ' . $psp . ' ')) return true;
            if ($squash && str_contains($squashed, $psq)) return true;
        }
    }
    return false;
}

// Plain-HTML version used only as a fallback when JavaScript is off.
function support_message_html(): string {
    $h = '<strong>You are not alone.</strong> If you are thinking about hurting yourself, please reach out right now:<ul class="hotline-list">';
    foreach (hotlines() as $l) {
        $h .= '<li><span>' . e($l['name']) . '</span> <strong>' . e($l['number']) . '</strong> <span class="muted">' . e($l['note']) . '</span></li>';
    }
    return $h . '</ul>';
}

// The crisis pop-up. Same markup on the login page and for students; the booking button only shows when signed in.
function crisis_dialog_html(bool $signedIn = false): string {
    $h = '<dialog id="crisis-dialog" class="crisis-dialog" aria-labelledby="crisis-h"><h2 id="crisis-h">You are not alone</h2>';
    $h .= $signedIn
        ? '<p>If you are thinking about hurting yourself or you feel unsafe, contact one of these right now. You matter, and you do not have to carry this alone.</p>'
        : '<p>If you are in crisis or thinking about hurting yourself, contact one of these right now. You do not need an account.</p>';
    $h .= '<ul class="hotline-list">';
    foreach (hotlines() as $l) {
        $h .= '<li>' . icon('phone', 16) . '<span>' . e($l['name']) . '</span> <strong>' . e($l['number']) . '</strong> <span class="muted">' . e($l['note']) . '</span></li>';
    }
    $h .= '</ul>';
    if (!HOTLINES_VERIFIED) $h .= '<p class="muted small">These numbers are pending verification by the school.</p>';
    $h .= '<div class="dialog-actions">';
    if ($signedIn) $h .= '<a class="btn" href="' . e(url('student/book.php')) . '">Book a session with a counselor</a>';
    return $h . '<button type="button" class="btn btn-quiet" id="crisis-close">Close</button></div></dialog>';
}
