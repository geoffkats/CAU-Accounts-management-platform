# Asset Management & Depreciation System

## Table of Contents
1. [Overview](#overview)
2. [Core Concepts](#core-concepts)
3. [Depreciation Methods](#depreciation-methods)
4. [Financial Formulas](#financial-formulas)
5. [Asset Lifecycle](#asset-lifecycle)
6. [Maintenance Tracking](#maintenance-tracking)
7. [Assignment Management](#assignment-management)
8. [Total Cost of Ownership](#total-cost-of-ownership)
9. [Practical Examples](#practical-examples)
10. [Best Practices](#best-practices)
11. [Technical Implementation](#technical-implementation)

---

## Overview

The Asset Management & Depreciation System provides comprehensive tracking of organizational assets from acquisition through disposal. It automates depreciation calculations, tracks maintenance costs, manages assignments, and provides accurate financial reporting.

### Key Features
- **Multiple Depreciation Methods**: Straight-line and declining balance
- **Automatic Calculations**: Depreciation computed from purchase date
- **Maintenance Tracking**: Full service history and cost tracking
- **Assignment Management**: Track who has custody of assets
- **Financial Reporting**: Book values, TCO, and depreciation schedules
- **Warranty Tracking**: Monitor warranty periods and maintenance needs

---

## Core Concepts

### 1. Asset Identification
Every asset is uniquely identified and tracked:

- **Asset Tag**: Unique identifier (barcode/QR code)
- **Serial Number**: Manufacturer's serial number
- **Category**: Classification (Computers, Vehicles, Furniture, etc.)
- **Location**: Physical location or department
- **Status**: Active, Maintenance, Disposed, etc.

### 2. Financial Attributes

**Purchase Price**
- Original acquisition cost
- Basis for depreciation calculations
- Includes all costs to make asset ready for use

**Salvage Value (Residual Value)**
- Estimated value at end of useful life
- Typically 10-20% of purchase price
- Minimum book value (asset never depreciates below this)

**Accumulated Depreciation**
- Total depreciation recorded to date
- Increases each accounting period
- Contra-asset account on balance sheet

**Book Value (Carrying Value)**
- Current value for accounting purposes
- Formula: `Book Value = Purchase Price - Accumulated Depreciation`
- Never goes below salvage value

**Useful Life**
- Expected years of service
- Determined by asset category and usage
- Used in straight-line depreciation

**Depreciation Rate**
- Annual percentage for declining balance method
- Higher rates = faster depreciation
- Typically 20-50% depending on asset type

---

## Depreciation Methods

### Method 1: Straight-Line Depreciation

**When to Use:**
- Assets with consistent value decline
- Buildings, furniture, fixtures
- Long-term equipment
- Simple, predictable depreciation

**Characteristics:**
- Equal depreciation each year
- Simple calculation
- Most commonly used method
- Easy to understand and explain

**Formula:**

```
Annual Depreciation = (Purchase Price - Salvage Value) / Useful Life Years

Monthly Depreciation = Annual Depreciation / 12

Accumulated Depreciation = Annual Depreciation × Age in Years

Book Value = Purchase Price - Accumulated Depreciation
```

**Example 1: Office Desk**

Initial Values:
- Purchase Price: UGX 1,500,000
- Salvage Value: UGX 150,000 (10%)
- Useful Life: 10 years
- Purchase Date: January 1, 2020

Calculations:
```
Depreciable Amount = 1,500,000 - 150,000 = 1,350,000
Annual Depreciation = 1,350,000 / 10 = 135,000 per year
Monthly Depreciation = 135,000 / 12 = 11,250 per month
```

Depreciation Schedule:

| Year | Begin Book Value | Depreciation | Accumulated Dep. | End Book Value |
|------|------------------|--------------|------------------|----------------|
| 2020 | 1,500,000 | 135,000 | 135,000 | 1,365,000 |
| 2021 | 1,365,000 | 135,000 | 270,000 | 1,230,000 |
| 2022 | 1,230,000 | 135,000 | 405,000 | 1,095,000 |
| 2023 | 1,095,000 | 135,000 | 540,000 | 960,000 |
| 2024 | 960,000 | 135,000 | 675,000 | 825,000 |
| 2025 | 825,000 | 135,000 | 810,000 | 690,000 |
| ... | ... | ... | ... | ... |
| 2029 | 285,000 | 135,000 | 1,350,000 | 150,000 |

**Example 2: Computer Equipment**

Initial Values:
- Purchase Price: UGX 4,000,000
- Salvage Value: UGX 400,000 (10%)
- Useful Life: 5 years
- Purchase Date: July 1, 2022

Calculations After 2.5 Years (January 1, 2025):
```
Annual Depreciation = (4,000,000 - 400,000) / 5 = 720,000
Accumulated Depreciation = 720,000 × 2.5 = 1,800,000
Current Book Value = 4,000,000 - 1,800,000 = 2,200,000
Monthly Expense = 720,000 / 12 = 60,000
```

Journal Entry (Monthly):
```
Dr. Depreciation Expense             60,000
    Cr. Accumulated Depreciation         60,000
```

---

### Method 2: Declining Balance Depreciation

**When to Use:**
- Assets losing value quickly in early years
- Vehicles, computers, technology
- High-tech equipment
- Assets with rapid obsolescence

**Characteristics:**
- Higher depreciation in early years
- Declining depreciation over time
- Accelerated depreciation method
- Better matches revenue generation pattern

**Formula:**

```
Year N Depreciation = Current Book Value × (Depreciation Rate / 100)

New Book Value = Previous Book Value - Year Depreciation

Stop when Book Value = Salvage Value
```

**Example 1: Computer (20% rate)**

Initial Values:
- Purchase Price: UGX 5,000,000
- Depreciation Rate: 20%
- Salvage Value: UGX 500,000
- Purchase Date: January 1, 2021

Year-by-Year Calculation:

**Year 1 (2021):**
```
Beginning Book Value: 5,000,000
Depreciation: 5,000,000 × 0.20 = 1,000,000
Ending Book Value: 5,000,000 - 1,000,000 = 4,000,000
```

**Year 2 (2022):**
```
Beginning Book Value: 4,000,000
Depreciation: 4,000,000 × 0.20 = 800,000
Ending Book Value: 4,000,000 - 800,000 = 3,200,000
```

**Year 3 (2023):**
```
Beginning Book Value: 3,200,000
Depreciation: 3,200,000 × 0.20 = 640,000
Ending Book Value: 3,200,000 - 640,000 = 2,560,000
```

**Year 4 (2024):**
```
Beginning Book Value: 2,560,000
Depreciation: 2,560,000 × 0.20 = 512,000
Ending Book Value: 2,560,000 - 512,000 = 2,048,000
```

**Year 5 (2025):**
```
Beginning Book Value: 2,048,000
Depreciation: 2,048,000 × 0.20 = 409,600
Ending Book Value: 2,048,000 - 409,600 = 1,638,400
```

Complete Schedule:

| Year | Begin Book Value | Rate | Depreciation | Accumulated | End Book Value |
|------|------------------|------|--------------|-------------|----------------|
| 2021 | 5,000,000 | 20% | 1,000,000 | 1,000,000 | 4,000,000 |
| 2022 | 4,000,000 | 20% | 800,000 | 1,800,000 | 3,200,000 |
| 2023 | 3,200,000 | 20% | 640,000 | 2,440,000 | 2,560,000 |
| 2024 | 2,560,000 | 20% | 512,000 | 2,952,000 | 2,048,000 |
| 2025 | 2,048,000 | 20% | 409,600 | 3,361,600 | 1,638,400 |
| 2026 | 1,638,400 | 20% | 327,680 | 3,689,280 | 1,310,720 |
| 2027 | 1,310,720 | 20% | 262,144 | 3,951,424 | 1,048,576 |
| 2028 | 1,048,576 | 20% | 209,715 | 4,161,139 | 838,861 |
| 2029 | 838,861 | 20% | 167,772 | 4,328,911 | 671,089 |
| 2030 | 671,089 | 20% | 171,089* | 4,500,000 | 500,000 |

*Final year adjusted to reach salvage value

**Example 2: Vehicle (40% rate)**

Initial Values:
- Purchase Price: UGX 30,000,000
- Depreciation Rate: 40%
- Salvage Value: UGX 3,000,000
- Purchase Date: January 1, 2022

Calculation:

**Year 1:**
```
Depreciation: 30,000,000 × 0.40 = 12,000,000
Book Value: 30,000,000 - 12,000,000 = 18,000,000
```

**Year 2:**
```
Depreciation: 18,000,000 × 0.40 = 7,200,000
Book Value: 18,000,000 - 7,200,000 = 10,800,000
```

**Year 3:**
```
Depreciation: 10,800,000 × 0.40 = 4,320,000
Book Value: 10,800,000 - 4,320,000 = 6,480,000
```

**Year 4:**
```
Depreciation: 6,480,000 × 0.40 = 2,592,000
Book Value: 6,480,000 - 2,592,000 = 3,888,000
```

**Year 5:**
```
Would be: 3,888,000 × 0.40 = 1,555,200
But this would drop below salvage value
Actual depreciation: 3,888,000 - 3,000,000 = 888,000
Final Book Value: 3,000,000 (salvage value reached)
```

Complete Schedule:

| Year | Begin Book Value | Rate | Depreciation | Accumulated | End Book Value |
|------|------------------|------|--------------|-------------|----------------|
| 2022 | 30,000,000 | 40% | 12,000,000 | 12,000,000 | 18,000,000 |
| 2023 | 18,000,000 | 40% | 7,200,000 | 19,200,000 | 10,800,000 |
| 2024 | 10,800,000 | 40% | 4,320,000 | 23,520,000 | 6,480,000 |
| 2025 | 6,480,000 | 40% | 2,592,000 | 26,112,000 | 3,888,000 |
| 2026 | 3,888,000 | 40% | 888,000* | 27,000,000 | 3,000,000 |

*Adjusted to not exceed salvage value

---

## Financial Formulas

### 1. Book Value Calculation

**Formula:**
```
Book Value = Purchase Price - Accumulated Depreciation
```

**Constraint:**
```
Book Value ≥ Salvage Value (never goes below salvage)
```

**Example:**
```
Purchase Price: UGX 10,000,000
Accumulated Depreciation: UGX 7,000,000
Book Value: 10,000,000 - 7,000,000 = 3,000,000
```

### 2. Depreciation Percentage

**Formula:**
```
Depreciation % = (Accumulated Depreciation / Purchase Price) × 100
```

**Interpretation:**
- 0-25%: New asset
- 25-50%: Mid-life asset
- 50-75%: Aging asset
- 75-100%: Near end of useful life

**Example:**
```
Purchase Price: UGX 8,000,000
Accumulated Depreciation: UGX 4,800,000
Depreciation % = (4,800,000 / 8,000,000) × 100 = 60%
Status: Aging asset, monitor for replacement
```

### 3. Remaining Useful Life

**Formula:**
```
Remaining Life = Useful Life Years - Age in Years
```

**Example:**
```
Useful Life: 10 years
Current Age: 3.5 years
Remaining Life: 10 - 3.5 = 6.5 years
```

### 4. Monthly Depreciation Expense

**Straight-Line:**
```
Monthly = [(Purchase Price - Salvage Value) / Useful Life Years] / 12
```

**Declining Balance:**
```
Monthly = [Current Book Value × (Rate / 100)] / 12
```

**Example (Straight-Line):**
```
Purchase Price: UGX 6,000,000
Salvage Value: UGX 600,000
Useful Life: 5 years
Monthly = [(6,000,000 - 600,000) / 5] / 12
Monthly = [1,080,000] / 12 = 90,000
```

### 5. Total Cost of Ownership (TCO)

**Basic Formula:**
```
TCO = Purchase Price + Total Maintenance Costs
```

**Comprehensive Formula:**
```
TCO = Purchase Price + Maintenance + Operating Costs + Training + Disposal
```

**Annual TCO:**
```
Annual TCO = Total TCO / Years Owned
```

**Example:**
```
Purchase Price: UGX 25,000,000
Maintenance (5 years): UGX 8,000,000
Operating Costs: UGX 5,000,000
Training: UGX 500,000
TCO = 25,000,000 + 8,000,000 + 5,000,000 + 500,000 = 38,500,000
Annual TCO = 38,500,000 / 5 = 7,700,000 per year
```

---

## Asset Lifecycle

### Phase 1: Acquisition

**Steps:**
1. Purchase or receive asset
2. Record in system with asset tag
3. Set depreciation parameters
4. Capture initial information

**Required Information:**
- Purchase price (including delivery, installation)
- Purchase date
- Supplier details
- Invoice number
- Serial number
- Warranty information

**Example Entry:**
```
Asset: Desktop Computer
Tag: COMP-2024-001
Purchase Price: UGX 3,500,000
Purchase Date: January 15, 2024
Supplier: Tech Solutions Ltd
Invoice: INV-12345
Serial: ABC123456789
Warranty: 3 years (expires Jan 15, 2027)
Category: Computer Equipment
Depreciation Method: Straight-Line
Useful Life: 5 years
Salvage Value: UGX 350,000 (10%)
```

### Phase 2: Active Use

**Activities:**
- Regular depreciation updates
- Maintenance tracking
- Assignment management
- Status monitoring

**Monthly Tasks:**
1. Calculate depreciation expense
2. Update accumulated depreciation
3. Update book value
4. Review maintenance needs
5. Check warranty status

**Journal Entry (Monthly):**
```
Dr. Depreciation Expense             58,333
    Cr. Accumulated Depreciation         58,333

Calculation: (3,500,000 - 350,000) / 5 / 12 = 58,333
```

### Phase 3: Maintenance

**Types of Maintenance:**

**Preventive Maintenance:**
- Scheduled regular service
- Extends useful life
- Reduces breakdowns
- Lower long-term costs

**Corrective Maintenance:**
- Repairs after failure
- Higher urgency
- Often more expensive
- May include parts replacement

**Example Maintenance Record:**
```
Asset: COMP-2024-001
Date: July 15, 2024
Type: Preventive
Work: System cleaning, software updates, hardware check
Cost: UGX 150,000
Performed By: IT Department
Next Service: October 15, 2024
```

### Phase 4: Disposal

**Disposal Reasons:**
- End of useful life
- Obsolescence
- Excessive maintenance costs
- Damage beyond repair
- Replacement with better technology

**Disposal Methods:**
- Sale to third party
- Trade-in for new asset
- Donation to charity
- Scrapping/recycling

**Financial Impact:**

**Gain on Disposal:**
```
If: Disposal Value > Book Value
Gain = Disposal Value - Book Value

Example:
Book Value: UGX 1,000,000
Sale Price: UGX 1,200,000
Gain: 1,200,000 - 1,000,000 = 200,000

Journal Entry:
Dr. Cash                    1,200,000
Dr. Accumulated Depreciation 2,500,000
    Cr. Asset Cost              3,500,000
    Cr. Gain on Disposal          200,000
```

**Loss on Disposal:**
```
If: Disposal Value < Book Value
Loss = Book Value - Disposal Value

Example:
Book Value: UGX 1,500,000
Sale Price: UGX 1,000,000
Loss: 1,500,000 - 1,000,000 = 500,000

Journal Entry:
Dr. Cash                    1,000,000
Dr. Accumulated Depreciation 2,000,000
Dr. Loss on Disposal          500,000
    Cr. Asset Cost              3,500,000
```

---

## Maintenance Tracking

### Maintenance Types

| Type | Frequency | Purpose | Cost Level |
|------|-----------|---------|------------|
| Preventive | Regular schedule | Prevent failures | Low |
| Predictive | Condition-based | Anticipate issues | Medium |
| Corrective | After failure | Fix problems | High |
| Emergency | Immediate | Critical repair | Very High |

### Maintenance Schedule Example

**Vehicle Maintenance Plan:**

| Service | Frequency | Estimated Cost | Notes |
|---------|-----------|----------------|-------|
| Oil Change | 5,000 km or 3 months | UGX 200,000 | Critical for engine life |
| Tire Rotation | 10,000 km | UGX 100,000 | Extends tire life |
| Brake Inspection | 6 months | UGX 150,000 | Safety critical |
| Annual Service | 12 months | UGX 800,000 | Comprehensive check |
| Major Service | 50,000 km | UGX 1,500,000 | Timing belt, etc. |

### Total Maintenance Cost Analysis

**3-Year Vehicle Example:**

```
Year 1 Maintenance:
- Oil changes (4×): 4 × 200,000 = 800,000
- Tire rotations (2×): 2 × 100,000 = 200,000
- Brake inspection (2×): 2 × 150,000 = 300,000
- Annual service: 800,000
- Minor repairs: 500,000
Year 1 Total: 2,600,000

Year 2 Maintenance:
- Oil changes (4×): 800,000
- Tire rotations (2×): 200,000
- Brake inspection (2×): 300,000
- Annual service: 800,000
- Tire replacement: 1,500,000
- Minor repairs: 400,000
Year 2 Total: 4,000,000

Year 3 Maintenance:
- Oil changes (4×): 800,000
- Tire rotations (2×): 200,000
- Brake inspection (2×): 300,000
- Annual service: 800,000
- Major service: 1,500,000
- Brake replacement: 800,000
- Minor repairs: 600,000
Year 3 Total: 5,000,000

3-Year Total Maintenance: 11,600,000
Average Annual: 3,866,667
```

### Maintenance Cost Ratio

**Formula:**
```
Maintenance Ratio = (Total Maintenance Cost / Purchase Price) × 100
```

**Decision Guidelines:**
- < 15%: Normal, acceptable
- 15-30%: Monitor closely
- 30-50%: Consider replacement
- > 50%: Replacement recommended

**Example:**
```
Purchase Price: UGX 30,000,000
3-Year Maintenance: UGX 11,600,000
Ratio: (11,600,000 / 30,000,000) × 100 = 38.7%

Interpretation: Monitor closely, approaching replacement threshold
```

---

## Assignment Management

### Assignment Tracking

**Purpose:**
- Establish accountability
- Track asset location
- Monitor usage patterns
- Identify responsible party for damage

### Assignment Process

**Step 1: Assignment Creation**
```
Asset: Laptop COMP-2024-015
Assigned To: John Doe (Staff ID: STF-123)
Assignment Date: March 1, 2024
Expected Return: December 31, 2024
Condition at Assignment: Good
Notes: For field work project
```

**Step 2: During Assignment**
- Asset marked as "Assigned"
- Assignee responsible for care
- Maintenance still tracked
- Damage reported immediately

**Step 3: Return Process**
```
Return Date: November 15, 2024
Condition on Return: Fair (minor scratches)
Return Notes: Normal wear and tear from field use
Days Assigned: 260 days
```

### Assignment Reports

**By Person:**
```
John Doe (STF-123)
Current Assignments:
- Laptop COMP-2024-015 (260 days)
- Projector PROJ-2024-008 (45 days)
- Camera CAM-2024-003 (12 days)

Assignment History:
- Tablet TAB-2023-020 (returned)
- Phone PHONE-2023-012 (returned)
```

**By Asset:**
```
Laptop COMP-2024-015
Purchase Date: January 5, 2024
Total Days in Service: 320
Days Assigned: 280 (87.5%)
Days Unassigned: 40 (12.5%)

Assignment History:
1. John Doe: Mar 1 - Nov 15, 2024 (260 days)
2. Jane Smith: Nov 20 - Dec 15, 2024 (25 days)
3. Unassigned: Dec 16, 2024 - Present
```

---

## Total Cost of Ownership

### TCO Components

**1. Acquisition Costs**
- Purchase price
- Delivery and shipping
- Installation and setup
- Initial configuration
- Training for users

**2. Operating Costs**
- Maintenance and repairs
- Consumables (ink, paper, etc.)
- Energy consumption
- Insurance
- Software licenses

**3. Disposal Costs**
- Removal and transportation
- Data wiping/destruction
- Environmental disposal fees
- Recycling costs

### TCO Analysis Example: Desktop Computer Fleet

**Scenario:** 50 desktop computers for office use

**Acquisition (Per Unit):**
```
Purchase Price: UGX 3,500,000
Delivery: UGX 50,000
Setup: UGX 100,000
Training: UGX 50,000
Total Acquisition: UGX 3,700,000

Fleet Total: 3,700,000 × 50 = 185,000,000
```

**Annual Operating (Per Unit):**
```
Maintenance: UGX 150,000
Software licenses: UGX 200,000
Power (@ UGX 1,000/day): UGX 365,000
Repairs (average): UGX 100,000
Total Annual Operating: UGX 815,000

Fleet Annual: 815,000 × 50 = 40,750,000
```

**5-Year Operating:**
```
Per Unit: 815,000 × 5 = 4,075,000
Fleet: 40,750,000 × 5 = 203,750,000
```

**Disposal (Per Unit):**
```
Data wiping: UGX 50,000
Transportation: UGX 20,000
Recycling: UGX 30,000
Total Disposal: UGX 100,000

Fleet Total: 100,000 × 50 = 5,000,000
```

**Total 5-Year TCO:**
```
Acquisition: 185,000,000
Operating (5 years): 203,750,000
Disposal: 5,000,000
Total TCO: 393,750,000

Per Unit TCO: 393,750,000 / 50 = 7,875,000
Annual Per Unit: 7,875,000 / 5 = 1,575,000
```

### TCO vs Purchase Price Ratio

**Formula:**
```
TCO Ratio = Total TCO / Purchase Price
```

**Example:**
```
Purchase Price: UGX 3,500,000
5-Year TCO: UGX 7,875,000
Ratio: 7,875,000 / 3,500,000 = 2.25

Interpretation: Operating costs are 125% of purchase price over 5 years
```

**Industry Benchmarks:**
- Computers: 1.5 - 2.5x purchase price
- Vehicles: 2.0 - 3.0x purchase price
- Manufacturing Equipment: 3.0 - 5.0x purchase price

---

## Practical Examples

### Example 1: Complete Computer Lifecycle

**Acquisition (January 1, 2022):**
```
Asset: Dell Latitude Laptop
Asset Tag: COMP-2022-025
Purchase Price: UGX 4,000,000
Salvage Value: UGX 400,000 (10%)
Useful Life: 5 years
Depreciation Method: Straight-Line
Warranty: 3 years (until Dec 31, 2024)
```

**Depreciation Calculation:**
```
Annual Depreciation = (4,000,000 - 400,000) / 5 = 720,000
Monthly Depreciation = 720,000 / 12 = 60,000
```

**Year 1 (2022):**
```
Beginning Book Value: 4,000,000
Depreciation: 720,000
Accumulated Depreciation: 720,000
Ending Book Value: 3,280,000
Depreciation %: 18%
Status: ✅ Under Warranty

Maintenance:
- None required (under warranty)
Total Maintenance Cost: 0
```

**Year 2 (2023):**
```
Beginning Book Value: 3,280,000
Depreciation: 720,000
Accumulated Depreciation: 1,440,000
Ending Book Value: 2,560,000
Depreciation %: 36%
Status: ✅ Under Warranty

Maintenance:
- Warranty repair (keyboard): UGX 0
Total Maintenance Cost: 0
```

**Year 3 (2024):**
```
Beginning Book Value: 2,560,000
Depreciation: 720,000
Accumulated Depreciation: 2,160,000
Ending Book Value: 1,840,000
Depreciation %: 54%
Status: ⚠️ Warranty expires Dec 31

Maintenance:
- Screen replacement (Nov): UGX 800,000
Total Maintenance Cost: 800,000
```

**Year 4 (2025):**
```
Beginning Book Value: 1,840,000
Depreciation: 720,000
Accumulated Depreciation: 2,880,000
Ending Book Value: 1,120,000
Depreciation %: 72%
Status: ⚠️ Aging, monitor performance

Maintenance:
- Battery replacement: UGX 400,000
- Hard drive upgrade: UGX 600,000
- Preventive service: UGX 150,000
Total Maintenance Cost: 1,150,000
```

**Year 5 (2026):**
```
Beginning Book Value: 1,120,000
Depreciation: 720,000
Accumulated Depreciation: 3,600,000
Ending Book Value: 400,000 (Salvage Value)
Depreciation %: 90%
Status: 🔴 Fully depreciated

Maintenance:
- Preventive service: UGX 150,000
Total Maintenance Cost: 150,000
```

**5-Year Summary:**
```
Total Depreciation: 3,600,000
Total Maintenance: 2,100,000
Total Cost of Ownership: 4,000,000 + 2,100,000 = 6,100,000
Annual TCO: 6,100,000 / 5 = 1,220,000
Final Book Value: 400,000

Decision: Consider replacement - maintenance costs increasing
```

### Example 2: Vehicle with Declining Balance

**Acquisition (January 1, 2020):**
```
Asset: Toyota Hiace Van
Asset Tag: VEH-2020-003
Purchase Price: UGX 50,000,000
Salvage Value: UGX 5,000,000 (10%)
Depreciation Method: Declining Balance (30%)
Registration: UAB 123A
Warranty: 3 years / 100,000 km
```

**Year-by-Year Analysis:**

**Year 1 (2020):**
```
Beginning Book Value: 50,000,000
Depreciation: 50,000,000 × 0.30 = 15,000,000
Accumulated Depreciation: 15,000,000
Ending Book Value: 35,000,000
Depreciation %: 30%
Kilometers: 25,000 km

Maintenance (under warranty):
- Oil changes (4×): UGX 800,000
- Regular service: UGX 500,000
Total Maintenance: 1,300,000

Operating Costs:
- Fuel: UGX 6,000,000
- Insurance: UGX 2,000,000
- Road tax: UGX 500,000
Total Operating: 8,500,000

Year 1 TCO: 9,800,000
```

**Year 2 (2021):**
```
Beginning Book Value: 35,000,000
Depreciation: 35,000,000 × 0.30 = 10,500,000
Accumulated Depreciation: 25,500,000
Ending Book Value: 24,500,000
Depreciation %: 51%
Kilometers: 53,000 km (28,000 added)

Maintenance:
- Oil changes: UGX 800,000
- Regular service: UGX 500,000
- Brake replacement: UGX 1,200,000
Total Maintenance: 2,500,000

Operating: 8,500,000
Year 2 TCO: 11,000,000
```

**Year 3 (2022):**
```
Beginning Book Value: 24,500,000
Depreciation: 24,500,000 × 0.30 = 7,350,000
Accumulated Depreciation: 32,850,000
Ending Book Value: 17,150,000
Depreciation %: 65.7%
Kilometers: 78,000 km (25,000 added)

Maintenance:
- Oil changes: UGX 800,000
- Major service (100k km coming): UGX 2,500,000
- Tire replacement: UGX 2,000,000
Total Maintenance: 5,300,000

Operating: 8,500,000
Year 3 TCO: 13,800,000
```

**Year 4 (2023):**
```
Beginning Book Value: 17,150,000
Depreciation: 17,150,000 × 0.30 = 5,145,000
Accumulated Depreciation: 37,995,000
Ending Book Value: 12,005,000
Depreciation %: 76%
Kilometers: 105,000 km (27,000 added)

Maintenance:
- Oil changes: UGX 800,000
- Regular service: UGX 500,000
- Suspension work: UGX 1,800,000
- Electrical repairs: UGX 900,000
Total Maintenance: 4,000,000

Operating: 8,500,000
Year 4 TCO: 12,500,000
```

**Year 5 (2024):**
```
Beginning Book Value: 12,005,000
Depreciation: 12,005,000 × 0.30 = 3,601,500
Accumulated Depreciation: 41,596,500
Ending Book Value: 8,403,500
Depreciation %: 83.2%
Kilometers: 132,000 km (27,000 added)

Maintenance:
- Oil changes: UGX 800,000
- Regular service: UGX 500,000
- Engine overhaul: UGX 4,500,000
- Various repairs: UGX 1,500,000
Total Maintenance: 7,300,000

Operating: 8,500,000
Year 5 TCO: 15,800,000
```

**5-Year Complete Summary:**
```
Purchase Price: 50,000,000
Total Depreciation: 41,596,500
Current Book Value: 8,403,500
Total Kilometers: 132,000

Maintenance Cost: 20,400,000
Operating Cost: 42,500,000
Total 5-Year Cost: 112,900,000
Annual Average: 22,580,000

Maintenance Ratio: (20,400,000 / 50,000,000) × 100 = 40.8%
Status: 🔴 High maintenance costs - consider replacement

Recommended Action: Sell or replace - approaching end of economic life
Estimated Sale Value: UGX 10,000,000 - 12,000,000
```

### Example 3: Office Furniture (Straight-Line)

**Acquisition (March 1, 2020):**
```
Asset: Conference Room Table Set
Asset Tag: FURN-2020-012
Purchase Price: UGX 8,000,000
Salvage Value: UGX 800,000 (10%)
Useful Life: 15 years
Depreciation Method: Straight-Line
```

**Depreciation Calculation:**
```
Annual Depreciation = (8,000,000 - 800,000) / 15 = 480,000
Monthly Depreciation = 480,000 / 12 = 40,000
```

**After 4 Years (March 1, 2024):**
```
Age: 4 years
Accumulated Depreciation: 480,000 × 4 = 1,920,000
Current Book Value: 8,000,000 - 1,920,000 = 6,080,000
Depreciation %: 24%

Total Maintenance (4 years): UGX 600,000
- Annual cleaning/polishing: UGX 100,000/year
- Minor repairs: UGX 200,000 total

Total Cost of Ownership: 8,600,000
Annual TCO: 8,600,000 / 4 = 2,150,000

Status: ✅ Excellent condition, low maintenance
Expected to last full 15 years
```

---

## Best Practices

### 1. Asset Acquisition

**DO:**
- ✅ Assign unique asset tags immediately
- ✅ Photograph assets for documentation
- ✅ Record all acquisition costs (delivery, installation)
- ✅ Set realistic salvage values (10-20% typical)
- ✅ Choose appropriate depreciation method for asset type
- ✅ Save all purchase documentation
- ✅ Register warranties in system

**DON'T:**
- ❌ Start using assets before recording in system
- ❌ Forget to include setup costs in purchase price
- ❌ Set salvage values too high or too low
- ❌ Use wrong depreciation method
- ❌ Lose warranty information

### 2. Depreciation Management

**DO:**
- ✅ Run depreciation updates monthly
- ✅ Review depreciation methods annually
- ✅ Update book values before financial statements
- ✅ Maintain consistent depreciation policy
- ✅ Document any method changes with justification

**DON'T:**
- ❌ Change depreciation methods mid-lifecycle
- ❌ Skip monthly depreciation updates
- ❌ Allow book value to go below salvage value
- ❌ Ignore fully depreciated assets still in use
- ❌ Forget to pause depreciation during extended downtime

### 3. Maintenance Planning

**DO:**
- ✅ Schedule preventive maintenance
- ✅ Track all maintenance costs
- ✅ Keep detailed maintenance logs
- ✅ Address issues promptly
- ✅ Use warranty coverage when available
- ✅ Plan major maintenance during off-peak times
- ✅ Budget for unexpected repairs (5-10% of value)

**DON'T:**
- ❌ Skip scheduled maintenance
- ❌ Wait for breakdowns (reactive only)
- ❌ Ignore maintenance cost trends
- ❌ Forget to schedule next maintenance
- ❌ Let warranties expire unused

### 4. Assignment Management

**DO:**
- ✅ Document condition at assignment
- ✅ Get assignee signature/acknowledgment
- ✅ Set expected return dates
- ✅ Track assignment history
- ✅ Inspect condition on return
- ✅ Report damage immediately

**DON'T:**
- ❌ Assign without documentation
- ❌ Allow indefinite assignments
- ❌ Ignore damaged returns
- ❌ Skip condition checks
- ❌ Lose track of asset locations

### 5. Financial Reporting

**DO:**
- ✅ Reconcile asset register monthly
- ✅ Include depreciation in management accounts
- ✅ Track TCO for major assets
- ✅ Review asset values quarterly
- ✅ Report disposals properly
- ✅ Maintain audit trail

**DON'T:**
- ❌ Report outdated book values
- ❌ Ignore fully depreciated assets
- ❌ Forget to remove disposed assets
- ❌ Mix personal and organizational assets
- ❌ Skip asset verification counts

### 6. Decision Making

**Replacement Criteria:**
- Maintenance costs > 30% of original cost
- Frequent breakdowns affecting operations
- Technology obsolescence
- Repair costs approaching replacement cost
- Safety concerns

**Retention Criteria:**
- Low maintenance costs
- Still meets operational needs
- Reliable performance
- Cost-effective to maintain
- No better alternatives available

---

## Technical Implementation

### Database Structure

**assets table:**
```sql
id
asset_category_id (foreign key)
program_id (foreign key)
asset_tag (unique)
name
description
brand
model
serial_number
purchase_price
purchase_date
supplier
invoice_number
salvage_value
depreciation_rate
depreciation_method (straight_line, declining_balance)
useful_life_years
accumulated_depreciation (calculated)
current_book_value (calculated)
status (draft, active, maintenance, disposed)
location
notes
warranty_expiry
assigned_to_staff_id (foreign key, nullable)
assigned_to_student (nullable)
assigned_date
disposal_date
disposal_value
disposal_reason
created_at
updated_at
deleted_at
```

**asset_categories table:**
```sql
id
name
code
description
default_depreciation_rate
depreciation_method
default_useful_life_years
is_active
created_at
updated_at
```

**asset_maintenance table:**
```sql
id
asset_id (foreign key)
type (preventive, corrective, emergency)
scheduled_date
completed_date
status (scheduled, completed, cancelled)
description
work_performed
performed_by
cost
invoice_number
downtime_hours
notes
parts_replaced
next_maintenance_date
created_at
updated_at
```

**asset_assignments table:**
```sql
id
asset_id (foreign key)
assigned_to_staff_id (foreign key, nullable)
assigned_to_student (nullable)
assigned_date
return_date
status (active, returned, overdue)
assignment_notes
return_notes
condition_on_return
created_at
updated_at
```

### Key Model Methods

**Asset Model:**
```php
// Static calculation methods
calculateStraightLineDepreciation($price, $salvage, $life, $age)
calculateDecliningBalanceDepreciation($price, $salvage, $rate, $age)

// Instance methods
calculateCurrentDepreciation()    // Calculate current accumulated depreciation
updateDepreciation()              // Update accumulated_depreciation and book_value fields
calculateMonthlyDepreciation()    // Calculate monthly expense

// Accessors (computed properties)
$asset->total_maintenance_cost      // Sum of all maintenance costs
$asset->total_cost_of_ownership     // Purchase + maintenance
$asset->is_under_warranty           // Check warranty status
$asset->is_fully_depreciated        // Check if reached salvage value
$asset->depreciation_percentage     // % of original value depreciated
$asset->maintenance_due             // Check if maintenance needed
$asset->assigned_to_name            // Name of person asset assigned to
```

### Scheduled Jobs

**Monthly Depreciation Update:**
```php
// Run on 1st of each month
Asset::active()->each(function ($asset) {
    $asset->updateDepreciation();
});
```

**Maintenance Reminders:**
```php
// Run daily
$dueAssets = Asset::whereHas('maintenanceRecords', function($q) {
    $q->where('next_maintenance_date', '<=', now()->addDays(7))
      ->where('status', 'completed');
})->get();

// Send notifications
```

**Warranty Expiry Alerts:**
```php
// Run daily
$expiringWarranties = Asset::whereBetween('warranty_expiry', [
    now(),
    now()->addDays(30)
])->get();

// Send notifications
```

### Reports Available

1. **Asset Register**: Complete list with current values
2. **Depreciation Schedule**: Future depreciation by year
3. **Maintenance Report**: Costs by asset/category
4. **TCO Analysis**: Total cost of ownership comparison
5. **Assignment Report**: Who has what
6. **Disposal Report**: Assets sold/scrapped with gains/losses
7. **Warranty Status**: Assets under/out of warranty
8. **Aging Analysis**: Assets by depreciation percentage

---

## Summary

The Asset Management & Depreciation System provides:

✅ **Automated depreciation** using industry-standard methods  
✅ **Complete maintenance tracking** with cost analysis  
✅ **Assignment management** for accountability  
✅ **TCO calculations** for better decision making  
✅ **Lifecycle management** from acquisition to disposal  
✅ **Financial reporting** with accurate book values  
✅ **Preventive maintenance** scheduling  
✅ **Warranty tracking** to maximize coverage  

**Key Takeaways:**
- Choose depreciation method appropriate for asset type
- Run monthly depreciation updates for accurate reporting
- Track all costs for true TCO analysis
- Schedule preventive maintenance to extend useful life
- Replace assets when maintenance costs become excessive
- Document everything for audit trail and decision making

For questions or support, refer to this documentation or contact the system administrator.
