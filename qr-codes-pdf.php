<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('vendor/autoload.php');

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use TCPDF;

include 'db.php'; // Adjust to your database connection script

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the version session variable is not set
if (!isset($_SESSION['version'])) {
    // Get the latest Git tag version
    $version = trim(exec('git describe --tags $(git rev-list --tags --max-count=1)'));

    // Set the session variable
    $_SESSION['version'] = $version;
} else {
    // Use the already set session variable
    $version = $_SESSION['version'];
}

$lockerIds = $db->query('select l.name as locker_name,l.id as locker_id,t.name as truck_name,l.truck_id from lockers l JOIN trucks t on l.truck_id= t.id order by t.id')->fetchAll(PDO::FETCH_ASSOC);



$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(7.375, 26, 7.375);
$pdf->SetAutoPageBreak(TRUE, 26);

$qrCodeSize = 45; // in mm
$gap = 5.08; // in mm
$labelsPerRow = 4;
$labelsPerColumn = 5;
$current_directory = dirname($_SERVER['REQUEST_URI']);

foreach ($lockerIds as $lockerId) {
    if ($index % ($labelsPerRow * $labelsPerColumn) == 0) {
        $pdf->AddPage();
    }

    $row = floor($index / $labelsPerRow) % $labelsPerColumn;
    $col = $index % $labelsPerRow;

    $x = $pdf->getMargins()['left'] + $col * ($qrCodeSize + $gap);
    $y = $pdf->getMargins()['top'] + $row * ($qrCodeSize + $gap);
    
    $locker_url = 'https://' . $_SERVER['HTTP_HOST'] . $current_directory . '/check_locker_items.php?truck_id=' . $lockerIds['truck_id'] . '&locker_id=' . $lockerIds['locker_id'];

    $qrCode = new QrCode($locker_url);
    $qrCode->setSize($qrCodeSize);

    $pdf->Image('@' . $qrCode->writeString(), $x, $y, $qrCodeSize, $qrCodeSize, 'PNG');
}

$pdf->Output('qrcodes.pdf', 'I');
?>