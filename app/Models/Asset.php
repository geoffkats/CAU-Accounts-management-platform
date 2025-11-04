<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * ============================================================================
 * ASSET MANAGEMENT & DEPRECIATION SYSTEM
 * ============================================================================
 * 
 * This model manages organizational assets including tracking, depreciation,
 * maintenance, and assignments. It supports multiple depreciation methods
 * and provides comprehensive asset lifecycle management.
 * 
 * CORE CONCEPTS:
 * --------------
 * 
 * 1. ASSET TRACKING
 *    - Unique asset tags for identification
 *    - Category classification
 *    - Location and assignment tracking
 *    - Status management (active, maintenance, disposed)
 * 
 * 2. DEPRECIATION METHODS
 *    a) Straight-Line Depreciation
 *       - Equal depreciation each year
 *       - Formula: Annual Depreciation = (Purchase Price - Salvage Value) / Useful Life
 *    
 *    b) Declining Balance Depreciation
 *       - Higher depreciation in early years
 *       - Formula: Annual Depreciation = Book Value × Depreciation Rate
 * 
 * 3. KEY FINANCIAL METRICS
 *    - Purchase Price: Original acquisition cost
 *    - Salvage Value: Estimated residual value at end of useful life
 *    - Accumulated Depreciation: Total depreciation to date
 *    - Book Value: Purchase Price - Accumulated Depreciation
 *    - Useful Life: Expected years of service
 * 
 * 4. ASSET LIFECYCLE STATES
 *    - Draft: Initial entry, not yet in service
 *    - Active: In use and depreciating
 *    - Maintenance: Under repair, may pause depreciation
 *    - Disposed: Sold, scrapped, or retired
 * 
 * 5. MAINTENANCE TRACKING
 *    - Scheduled maintenance
 *    - Preventive maintenance costs
 *    - Downtime tracking
 *    - Total Cost of Ownership calculation
 * 
 * 6. ASSIGNMENT MANAGEMENT
 *    - Assign to staff or students
 *    - Track assignment history
 *    - Monitor return conditions
 *    - Accountability and responsibility
 * 
 * DEPRECIATION FORMULAS:
 * ----------------------
 * 
 * STRAIGHT-LINE METHOD:
 * Annual Depreciation = (Purchase Price - Salvage Value) / Useful Life Years
 * Monthly Depreciation = Annual Depreciation / 12
 * Accumulated Depreciation = Annual Depreciation × Age in Years
 * Book Value = Purchase Price - Accumulated Depreciation
 * 
 * Example:
 * - Computer purchased for UGX 3,000,000
 * - Salvage value UGX 300,000
 * - Useful life 5 years
 * - After 2 years:
 *   Annual Depreciation = (3,000,000 - 300,000) / 5 = 540,000 per year
 *   Accumulated Depreciation = 540,000 × 2 = 1,080,000
 *   Book Value = 3,000,000 - 1,080,000 = 1,920,000
 * 
 * DECLINING BALANCE METHOD:
 * Year 1 Depreciation = Purchase Price × (Depreciation Rate / 100)
 * Year 2 Depreciation = (Purchase Price - Year 1 Dep.) × (Depreciation Rate / 100)
 * Year N Depreciation = Previous Book Value × (Depreciation Rate / 100)
 * 
 * Example with 40% rate:
 * - Vehicle purchased for UGX 20,000,000
 * - Depreciation rate 40%
 * - Salvage value UGX 2,000,000
 * - Year 1: 20,000,000 × 0.40 = 8,000,000 (Book Value: 12,000,000)
 * - Year 2: 12,000,000 × 0.40 = 4,800,000 (Book Value: 7,200,000)
 * - Year 3: 7,200,000 × 0.40 = 2,880,000 (Book Value: 4,320,000)
 * - Stops at salvage value
 * 
 * TOTAL COST OF OWNERSHIP:
 * TCO = Purchase Price + Total Maintenance Costs + Operating Costs
 * 
 * BEST PRACTICES:
 * ---------------
 * 1. DO run updateDepreciation() monthly for accurate financial statements
 * 2. DO set realistic salvage values (typically 10-20% of purchase price)
 * 3. DO choose depreciation method matching asset type:
 *    - Straight-line: Furniture, buildings, long-term equipment
 *    - Declining balance: Vehicles, computers, technology
 * 4. DO schedule regular maintenance to extend useful life
 * 5. DO track all maintenance costs for TCO analysis
 * 
 * DON'T:
 * 1. DON'T depreciate below salvage value
 * 2. DON'T change depreciation method after asset is in service
 * 3. DON'T forget to update book value when disposing assets
 * 4. DON'T ignore warranty periods for maintenance planning
 * 5. DON'T assign critical assets without proper documentation
 * 
 * ============================================================================
 */
class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_category_id',
        'program_id',
        'asset_tag',
        'name',
        'description',
        'photo_path',
        'brand',
        'model',
        'serial_number',
        'purchase_price',
        'purchase_date',
        'supplier',
        'invoice_number',
        'salvage_value',
        'depreciation_rate',
        'depreciation_method',
        'useful_life_years',
        'accumulated_depreciation',
        'current_book_value',
        'status',
        'location',
        'notes',
        'warranty_expiry',
        'assigned_to_staff_id',
        'assigned_to_student',
        'assigned_date',
        'disposal_date',
        'disposal_value',
        'disposal_reason',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'disposal_value' => 'decimal:2',
        'useful_life_years' => 'integer',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'assigned_date' => 'date',
        'disposal_date' => 'date',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function assignedToStaff()
    {
        return $this->belongsTo(Staff::class, 'assigned_to_staff_id');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->where('status', 'active')->latest();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('asset_category_id', $categoryId);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to_staff_id')
            ->orWhereNotNull('assigned_to_student');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to_staff_id')
            ->whereNull('assigned_to_student');
    }

    public function scopeWarrantyExpiring($query, $days = 30)
    {
        return $query->whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [now(), now()->addDays($days)]);
    }

    // Depreciation Calculations
    
    /**
     * Calculate straight-line depreciation
     * 
     * FORMULA:
     * Annual Depreciation = (Purchase Price - Salvage Value) / Useful Life Years
     * Total Depreciation = Annual Depreciation × Age in Years
     * 
     * CHARACTERISTICS:
     * - Equal depreciation each year
     * - Simple and predictable
     * - Best for assets with consistent value decline
     * - Commonly used for buildings, furniture, fixtures
     * 
     * CALCULATION STEPS:
     * 1. Calculate depreciable amount: Purchase Price - Salvage Value
     * 2. Divide by useful life to get annual depreciation
     * 3. Multiply by age to get accumulated depreciation
     * 4. Ensure we don't depreciate below salvage value
     * 
     * EXAMPLE 1 - Office Furniture:
     * - Purchase Price: UGX 1,500,000
     * - Salvage Value: UGX 150,000
     * - Useful Life: 10 years
     * - Age: 3 years
     * 
     * Calculation:
     * Depreciable Amount = 1,500,000 - 150,000 = 1,350,000
     * Annual Depreciation = 1,350,000 / 10 = 135,000
     * Total Depreciation = 135,000 × 3 = 405,000
     * Book Value = 1,500,000 - 405,000 = 1,095,000
     * 
     * EXAMPLE 2 - Building:
     * - Purchase Price: UGX 50,000,000
     * - Salvage Value: UGX 10,000,000
     * - Useful Life: 25 years
     * - Age: 5 years
     * 
     * Calculation:
     * Annual Depreciation = (50,000,000 - 10,000,000) / 25 = 1,600,000
     * Total Depreciation = 1,600,000 × 5 = 8,000,000
     * Book Value = 50,000,000 - 8,000,000 = 42,000,000
     * 
     * @param float $purchasePrice Original cost of the asset
     * @param float $salvageValue Estimated residual value
     * @param int $usefulLifeYears Expected years of service
     * @param float $ageInYears Current age of asset
     * @return float Total accumulated depreciation
     */
    public static function calculateStraightLineDepreciation($purchasePrice, $salvageValue, $usefulLifeYears, $ageInYears)
    {
        if ($usefulLifeYears <= 0) return 0;
        
        $annualDepreciation = ($purchasePrice - $salvageValue) / $usefulLifeYears;
        $totalDepreciation = $annualDepreciation * $ageInYears;
        
        // Don't depreciate below salvage value
        return min($totalDepreciation, $purchasePrice - $salvageValue);
    }

    /**
     * Calculate declining balance depreciation
     * 
     * FORMULA:
     * Year N Depreciation = Current Book Value × (Depreciation Rate / 100)
     * Book Value = Previous Book Value - Year Depreciation
     * 
     * CHARACTERISTICS:
     * - Higher depreciation in early years
     * - Accelerated depreciation method
     * - Best for assets losing value quickly
     * - Commonly used for vehicles, computers, technology
     * 
     * CALCULATION STEPS:
     * 1. Start with purchase price as initial book value
     * 2. For each year:
     *    a. Calculate depreciation: Book Value × Rate
     *    b. Check if depreciation would go below salvage value
     *    c. If yes, depreciate only to salvage value and stop
     *    d. If no, apply full depreciation and continue
     * 3. Sum all yearly depreciation amounts
     * 
     * EXAMPLE 1 - Computer (20% rate):
     * - Purchase Price: UGX 3,000,000
     * - Depreciation Rate: 20%
     * - Salvage Value: UGX 300,000
     * - Age: 3 years
     * 
     * Calculation:
     * Year 1: 3,000,000 × 0.20 = 600,000 (Book Value: 2,400,000)
     * Year 2: 2,400,000 × 0.20 = 480,000 (Book Value: 1,920,000)
     * Year 3: 1,920,000 × 0.20 = 384,000 (Book Value: 1,536,000)
     * Total Depreciation = 600,000 + 480,000 + 384,000 = 1,464,000
     * Final Book Value = 1,536,000
     * 
     * EXAMPLE 2 - Vehicle (40% rate):
     * - Purchase Price: UGX 25,000,000
     * - Depreciation Rate: 40%
     * - Salvage Value: UGX 2,500,000
     * - Age: 4 years
     * 
     * Calculation:
     * Year 1: 25,000,000 × 0.40 = 10,000,000 (Book Value: 15,000,000)
     * Year 2: 15,000,000 × 0.40 = 6,000,000 (Book Value: 9,000,000)
     * Year 3: 9,000,000 × 0.40 = 3,600,000 (Book Value: 5,400,000)
     * Year 4: 5,400,000 × 0.40 = 2,160,000 (Book Value: 3,240,000)
     * Total Depreciation = 10,000,000 + 6,000,000 + 3,600,000 + 2,160,000 = 21,760,000
     * Final Book Value = 3,240,000
     * 
     * EXAMPLE 3 - Reaching Salvage Value:
     * - Purchase Price: UGX 5,000,000
     * - Depreciation Rate: 50%
     * - Salvage Value: UGX 1,000,000
     * 
     * Year 1: 5,000,000 × 0.50 = 2,500,000 (Book Value: 2,500,000)
     * Year 2: 2,500,000 × 0.50 = 1,250,000 (Book Value: 1,250,000)
     * Year 3: Would be 625,000, but stops at salvage value
     *         Only 250,000 depreciation to reach 1,000,000
     * Total Depreciation = 2,500,000 + 1,250,000 + 250,000 = 4,000,000
     * 
     * @param float $purchasePrice Original cost of the asset
     * @param float $salvageValue Estimated residual value
     * @param float $depreciationRate Annual depreciation percentage
     * @param float $ageInYears Current age of asset
     * @return float Total accumulated depreciation
     */
    public static function calculateDecliningBalanceDepreciation($purchasePrice, $salvageValue, $depreciationRate, $ageInYears)
    {
        $bookValue = $purchasePrice;
        $totalDepreciation = 0;
        
        for ($year = 0; $year < $ageInYears; $year++) {
            $yearlyDepreciation = $bookValue * ($depreciationRate / 100);
            
            // Don't depreciate below salvage value
            if ($bookValue - $yearlyDepreciation < $salvageValue) {
                $totalDepreciation += ($bookValue - $salvageValue);
                break;
            }
            
            $totalDepreciation += $yearlyDepreciation;
            $bookValue -= $yearlyDepreciation;
        }
        
        return $totalDepreciation;
    }

    /**
     * Calculate current depreciation for this asset
     * 
     * Automatically selects the appropriate depreciation method based on
     * the asset's configuration and calculates accumulated depreciation
     * from purchase date to current date.
     * 
     * PROCESS:
     * 1. Calculate age of asset in years (with decimal precision)
     * 2. Apply configured depreciation method
     * 3. Return total accumulated depreciation
     * 
     * EXAMPLE - Straight-Line Asset:
     * - Computer purchased 2.5 years ago
     * - Purchase Price: UGX 4,000,000
     * - Salvage Value: UGX 400,000
     * - Useful Life: 5 years
     * 
     * Calculation:
     * Age = 2.5 years
     * Annual Depreciation = (4,000,000 - 400,000) / 5 = 720,000
     * Total Depreciation = 720,000 × 2.5 = 1,800,000
     * 
     * EXAMPLE - Declining Balance Asset:
     * - Vehicle purchased 1.8 years ago
     * - Purchase Price: UGX 30,000,000
     * - Rate: 30%
     * 
     * Calculation uses full years only (1 year):
     * Year 1: 30,000,000 × 0.30 = 9,000,000
     * Total Depreciation = 9,000,000
     * 
     * @return float Current accumulated depreciation
     */
    public function calculateCurrentDepreciation()
    {
        $ageInYears = Carbon::parse($this->purchase_date)->diffInYears(now(), true);
        
        switch ($this->depreciation_method) {
            case 'straight_line':
                return self::calculateStraightLineDepreciation(
                    $this->purchase_price,
                    $this->salvage_value,
                    $this->useful_life_years,
                    $ageInYears
                );
                
            case 'declining_balance':
                return self::calculateDecliningBalanceDepreciation(
                    $this->purchase_price,
                    $this->salvage_value,
                    $this->depreciation_rate,
                    $ageInYears
                );
                
            default:
                return 0;
        }
    }

    /**
     * Update accumulated depreciation and book value
     * 
     * PROCESS:
     * 1. Calculate current accumulated depreciation
     * 2. Update accumulated_depreciation field
     * 3. Calculate and update current book value
     * 4. Ensure book value doesn't go below salvage value
     * 5. Save changes to database
     * 
     * WHEN TO USE:
     * - Monthly during financial close
     * - Before generating financial statements
     * - After asset purchase date corrections
     * - When reviewing asset values
     * 
     * EXAMPLE:
     * Asset with:
     * - Purchase Price: UGX 5,000,000
     * - Calculated Depreciation: UGX 2,000,000
     * - Salvage Value: UGX 500,000
     * 
     * Updates:
     * accumulated_depreciation = 2,000,000
     * current_book_value = max(5,000,000 - 2,000,000, 500,000) = 3,000,000
     * 
     * @return $this
     */
    public function updateDepreciation()
    {
        $this->accumulated_depreciation = $this->calculateCurrentDepreciation();
        $this->current_book_value = max(
            $this->purchase_price - $this->accumulated_depreciation,
            $this->salvage_value
        );
        $this->save();
        
        return $this;
    }

    /**
     * Calculate monthly depreciation expense
     * 
     * FORMULA (Straight-Line):
     * Monthly Depreciation = [(Purchase Price - Salvage Value) / Useful Life] / 12
     * 
     * FORMULA (Declining Balance):
     * Monthly Depreciation = [Current Book Value × Rate] / 12
     * 
     * PURPOSE:
     * - Allocate depreciation expense to monthly periods
     * - Support monthly financial reporting
     * - Track depreciation for management accounts
     * 
     * BEHAVIOR:
     * - Returns 0 if asset is fully depreciated
     * - Uses current book value for declining balance
     * - Consistent monthly amount for straight-line
     * 
     * EXAMPLE 1 - Straight-Line (Computer):
     * - Purchase Price: UGX 3,600,000
     * - Salvage Value: UGX 360,000
     * - Useful Life: 5 years
     * - Not yet fully depreciated
     * 
     * Calculation:
     * Annual Depreciation = (3,600,000 - 360,000) / 5 = 648,000
     * Monthly Depreciation = 648,000 / 12 = 54,000
     * 
     * Journal Entry (monthly):
     * Dr. Depreciation Expense     54,000
     *   Cr. Accumulated Depreciation   54,000
     * 
     * EXAMPLE 2 - Declining Balance (Vehicle):
     * - Purchase Price: UGX 20,000,000
     * - Current Book Value: UGX 12,000,000 (after 2 years)
     * - Rate: 40%
     * 
     * Calculation:
     * Annual Depreciation = 12,000,000 × 0.40 = 4,800,000
     * Monthly Depreciation = 4,800,000 / 12 = 400,000
     * 
     * Note: Monthly amount decreases as book value decreases
     * 
     * EXAMPLE 3 - Fully Depreciated Asset:
     * - Age exceeds useful life
     * - Monthly Depreciation = 0 (no more depreciation)
     * 
     * @return float Monthly depreciation expense
     */
    public function calculateMonthlyDepreciation()
    {
        $ageInYears = Carbon::parse($this->purchase_date)->diffInYears(now(), true);
        
        if ($ageInYears >= $this->useful_life_years) {
            return 0; // Fully depreciated
        }
        
        switch ($this->depreciation_method) {
            case 'straight_line':
                $annualDepreciation = ($this->purchase_price - $this->salvage_value) / $this->useful_life_years;
                return $annualDepreciation / 12;
                
            case 'declining_balance':
                $currentBookValue = $this->purchase_price - $this->accumulated_depreciation;
                $annualDepreciation = $currentBookValue * ($this->depreciation_rate / 100);
                return $annualDepreciation / 12;
                
            default:
                return 0;
        }
    }

    /**
     * Get total maintenance cost
     * 
     * CALCULATION:
     * Total Maintenance Cost = Σ(All Maintenance Records Cost)
     * 
     * INCLUDES:
     * - Preventive maintenance
     * - Corrective repairs
     * - Parts replacement
     * - Service contracts
     * 
     * PURPOSE:
     * - Calculate Total Cost of Ownership
     * - Analyze maintenance efficiency
     * - Budget future maintenance needs
     * - Make replacement decisions
     * 
     * EXAMPLE:
     * Vehicle with maintenance records:
     * - Oil change: UGX 200,000
     * - Tire replacement: UGX 1,500,000
     * - Brake service: UGX 800,000
     * - Annual service: UGX 500,000
     * 
     * Total Maintenance Cost = 200,000 + 1,500,000 + 800,000 + 500,000
     *                        = 3,000,000
     * 
     * @return float Sum of all maintenance costs
     */
    public function getTotalMaintenanceCostAttribute()
    {
        return $this->maintenanceRecords()->sum('cost');
    }

    /**
     * Get total cost of ownership
     * 
     * FORMULA:
     * TCO = Purchase Price + Total Maintenance Costs
     * 
     * COMPREHENSIVE TCO:
     * TCO = Purchase Price + Maintenance + Operating Costs + Training + Disposal
     * 
     * PURPOSE:
     * - True asset cost analysis
     * - Make better purchase decisions
     * - Compare asset performance
     * - Plan asset replacement
     * 
     * EXAMPLE 1 - Computer:
     * - Purchase Price: UGX 4,000,000
     * - Maintenance (3 years): UGX 300,000
     * - TCO = 4,000,000 + 300,000 = 4,300,000
     * - Annual TCO = 4,300,000 / 3 = 1,433,333 per year
     * 
     * EXAMPLE 2 - Vehicle:
     * - Purchase Price: UGX 30,000,000
     * - Maintenance (5 years): UGX 8,500,000
     * - TCO = 30,000,000 + 8,500,000 = 38,500,000
     * - Annual TCO = 38,500,000 / 5 = 7,700,000 per year
     * 
     * DECISION MAKING:
     * - If maintenance costs exceed 30% of purchase price, consider replacement
     * - Compare TCO across similar assets to identify best performers
     * - High TCO assets may need different maintenance strategies
     * 
     * @return float Total cost including purchase and maintenance
     */
    public function getTotalCostOfOwnershipAttribute()
    {
        return $this->purchase_price + $this->total_maintenance_cost;
    }

    /**
     * Check if warranty is valid
     * 
     * LOGIC:
     * - Asset has warranty_expiry date
     * - Warranty expiry date is in the future
     * 
     * PURPOSE:
     * - Determine if repairs are covered
     * - Plan maintenance timing
     * - Track warranty benefits
     * 
     * EXAMPLE:
     * Computer purchased Jan 1, 2024
     * Warranty expires Dec 31, 2025
     * Checked on June 1, 2024
     * Result: true (still under warranty)
     * 
     * @return bool True if warranty is still valid
     */
    public function getIsUnderWarrantyAttribute()
    {
        return $this->warranty_expiry && $this->warranty_expiry->isFuture();
    }

    /**
     * Check if asset is fully depreciated
     * 
     * LOGIC:
     * Current Book Value ≤ Salvage Value
     * 
     * INTERPRETATION:
     * - true: Asset reached end of depreciable life
     * - false: Asset still depreciating
     * 
     * IMPLICATIONS:
     * - No more depreciation expense
     * - Asset may still be in use
     * - Consider disposal or revaluation
     * 
     * EXAMPLE:
     * - Purchase Price: UGX 5,000,000
     * - Salvage Value: UGX 500,000
     * - Current Book Value: UGX 500,000
     * - Result: true (fully depreciated)
     * 
     * @return bool True if fully depreciated
     */
    public function getIsFullyDepreciatedAttribute()
    {
        return $this->current_book_value <= $this->salvage_value;
    }

    /**
     * Get depreciation percentage
     * 
     * FORMULA:
     * Depreciation % = (Accumulated Depreciation / Purchase Price) × 100
     * 
     * PURPOSE:
     * - Quick view of asset age/condition
     * - Compare depreciation across assets
     * - Identify assets needing replacement
     * 
     * INTERPRETATION:
     * - 0-25%: New asset, minimal depreciation
     * - 25-50%: Mid-life asset
     * - 50-75%: Aging asset, monitor for replacement
     * - 75-100%: Near end of life, plan replacement
     * 
     * EXAMPLE 1:
     * - Purchase Price: UGX 10,000,000
     * - Accumulated Depreciation: UGX 2,500,000
     * - Depreciation % = (2,500,000 / 10,000,000) × 100 = 25%
     * - Status: New/Mid-life
     * 
     * EXAMPLE 2:
     * - Purchase Price: UGX 5,000,000
     * - Accumulated Depreciation: UGX 4,000,000
     * - Depreciation % = (4,000,000 / 5,000,000) × 100 = 80%
     * - Status: Near end of life
     * 
     * @return float Percentage of asset depreciated
     */
    public function getDepreciationPercentageAttribute()
    {
        if ($this->purchase_price <= 0) return 0;
        return ($this->accumulated_depreciation / $this->purchase_price) * 100;
    }

    /**
     * Check if maintenance is due
     * 
     * LOGIC:
     * 1. Find last completed maintenance
     * 2. Check if next_maintenance_date exists
     * 3. Return true if date has passed or no maintenance history
     * 
     * PURPOSE:
     * - Schedule preventive maintenance
     * - Avoid unexpected breakdowns
     * - Extend asset useful life
     * 
     * EXAMPLE:
     * Vehicle last serviced on Mar 1, 2024
     * Next service due Jun 1, 2024
     * Checked on Jun 15, 2024
     * Result: true (maintenance overdue)
     * 
     * @return bool True if maintenance is due
     */
    public function getMaintenanceDueAttribute()
    {
        $lastMaintenance = $this->maintenanceRecords()
            ->where('status', 'completed')
            ->latest('completed_date')
            ->first();
            
        if (!$lastMaintenance) return true;
        
        // Check if next maintenance date has passed
        if ($lastMaintenance->next_maintenance_date) {
            return Carbon::parse($lastMaintenance->next_maintenance_date)->isPast();
        }
        
        return false;
    }

    /**
     * Get assigned to name
     */
    public function getAssignedToNameAttribute()
    {
        if ($this->assigned_to_staff_id && $this->assignedToStaff) {
            return $this->assignedToStaff->full_name;
        }
        
        if ($this->assigned_to_student) {
            return $this->assigned_to_student;
        }
        
        return 'Unassigned';
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) return null;

        if (Storage::disk('public')->exists($this->photo_path)) {
            return Storage::disk('public')->url($this->photo_path);
        }

        return null;
    }
}
