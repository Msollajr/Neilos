<?php
/**
 * Test Suite: SOF Partner KYC Data Sourcing & Validation
 */
require_once dirname(__DIR__) . '/app/config/database.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/helpers/format.php';
require_once dirname(__DIR__) . '/app/helpers/workflow.php';

$db = getDB();

echo "====================================================\n";
echo "TEST SUITE: SOF PARTNER KYC SOURCING & VALIDATION\n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest($condition, $testName, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passed++;
    } else {
        echo " [FAIL] $testName\n";
        if ($details) echo "        Details: $details\n";
        $failed++;
    }
}

// 1. Fetch existing partners
$stmt = $db->query("SELECT p.id, p.name, pka.id as kyc_id, pka.status, pka.registered_name, pka.tech_supervisor_name, pka.billing_contact_name 
    FROM partners p 
    LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id");
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($partners) . " partner / KYC rows in database.\n";

// Test 1: getAuthoritativePartnerKyc returns expected fields
if (!empty($partners)) {
    $pId = (int)$partners[0]['id'];
    $kyc = getAuthoritativePartnerKyc($db, $pId);
    
    assertTest(
        is_array($kyc),
        "Test 1: getAuthoritativePartnerKyc returns array for partner ID $pId"
    );
    
    assertTest(
        array_key_exists('company_name', $kyc) &&
        array_key_exists('tech_contact_name', $kyc) &&
        array_key_exists('tech_contact_phone', $kyc) &&
        array_key_exists('tech_contact_email', $kyc) &&
        array_key_exists('billing_contact_name', $kyc) &&
        array_key_exists('billing_contact_phone', $kyc) &&
        array_key_exists('billing_contact_email', $kyc) &&
        array_key_exists('is_complete', $kyc),
        "Test 2: KYC record contains all required normalized keys"
    );
}

// Test 3: Create mock Partner & complete KYC to test exact matching
$db->beginTransaction();
try {
    // Insert Mock Partner
    $db->prepare("INSERT INTO partners (name, status, kyc_type) VALUES ('Savanna Test Partner', 'Active', 'Partner')")->execute();
    $mockPartnerId = (int)$db->lastInsertId();

    // Insert Mock Approved KYC with distinct contacts
    $db->prepare("INSERT INTO partner_kyc_applications (
        partner_id, status, registered_name, trading_name, registration_number, tin, office_address, city_region,
        tech_supervisor_name, tech_supervisor_phone, tech_supervisor_email,
        billing_contact_name, billing_contact_phone, billing_contact_email
    ) VALUES (
        ?, 'Approved', 'Savanna', 'Savanna SP', 'REG12345', 'TIN98765', '123 Tech Park', 'Dar es Salaam',
        'Savanna Technical Lead', '0712000008', 'tech@savannasp.co.tz',
        'Savanna Billing Manager', '+255713887984', 'billing@savannasp.co.tz'
    )")->execute([$mockPartnerId]);
    $mockKycId = (int)$db->lastInsertId();

    $resolvedKyc = getAuthoritativePartnerKyc($db, $mockPartnerId);

    assertTest(
        $resolvedKyc['company_name'] === 'Savanna',
        "Test 3: Company Name mapped from KYC registered_name ('Savanna')",
        "Got: " . ($resolvedKyc['company_name'] ?? '')
    );

    assertTest(
        $resolvedKyc['tech_contact_name'] === 'Savanna Technical Lead' &&
        $resolvedKyc['tech_contact_phone'] === '0712000008' &&
        $resolvedKyc['tech_contact_email'] === 'tech@savannasp.co.tz',
        "Test 4: Technical Contact matches KYC exactly",
        "Got: " . json_encode([$resolvedKyc['tech_contact_name'], $resolvedKyc['tech_contact_phone'], $resolvedKyc['tech_contact_email']])
    );

    assertTest(
        $resolvedKyc['billing_contact_name'] === 'Savanna Billing Manager' &&
        $resolvedKyc['billing_contact_phone'] === '+255713887984' &&
        $resolvedKyc['billing_contact_email'] === 'billing@savannasp.co.tz',
        "Test 5: Billing Contact matches KYC exactly",
        "Got: " . json_encode([$resolvedKyc['billing_contact_name'], $resolvedKyc['billing_contact_phone'], $resolvedKyc['billing_contact_email']])
    );

    assertTest(
        $resolvedKyc['is_complete'] === true && empty($resolvedKyc['missing_fields']),
        "Test 6: Complete KYC flagged as complete"
    );

    // Test 7: Incomplete KYC (missing tech email & billing phone)
    $db->prepare("UPDATE partner_kyc_applications SET tech_supervisor_email = '', billing_contact_phone = '' WHERE id = ?")->execute([$mockKycId]);
    $incompleteKyc = getAuthoritativePartnerKyc($db, $mockPartnerId);

    assertTest(
        $incompleteKyc['is_complete'] === false &&
        in_array('Technical Contact Email', $incompleteKyc['missing_fields']) &&
        in_array('Billing Contact Phone', $incompleteKyc['missing_fields']),
        "Test 7: Missing required contact fields correctly identified & flagged",
        "Missing fields: " . implode(', ', $incompleteKyc['missing_fields'])
    );

    // Test 8: Cross-partner isolation
    $db->prepare("INSERT INTO partners (name, status, kyc_type) VALUES ('Partner B', 'Active', 'Partner')")->execute();
    $partnerBId = (int)$db->lastInsertId();
    $db->prepare("INSERT INTO partner_kyc_applications (
        partner_id, status, registered_name, registration_number, tin, office_address, city_region,
        tech_supervisor_name, tech_supervisor_phone, tech_supervisor_email,
        billing_contact_name, billing_contact_phone, billing_contact_email
    ) VALUES (
        ?, 'Approved', 'Partner B Ltd', 'REG999', 'TIN999', '456 West Road', 'Arusha',
        'Partner B Tech', '0755000001', 'btech@partner.co.tz',
        'Partner B Billing', '0755000002', 'bbilling@partner.co.tz'
    )")->execute([$partnerBId]);

    $kycB = getAuthoritativePartnerKyc($db, $partnerBId);
    assertTest(
        $kycB['company_name'] === 'Partner B Ltd' && $kycB['tech_contact_name'] === 'Partner B Tech' && $resolvedKyc['company_name'] === 'Savanna',
        "Test 8: Cross-partner KYC data is strictly isolated"
    );

    // Rollback test data
    $db->rollBack();
} catch (Exception $e) {
    $db->rollBack();
    echo "Exception during test: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n====================================================\n";
echo "SUMMARY: Passed: $passed | Failed: $failed\n";
echo "====================================================\n";
