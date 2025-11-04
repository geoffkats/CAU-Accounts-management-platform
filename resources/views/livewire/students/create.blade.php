<?php

use App\Models\Student;
use App\Models\Program;
use Livewire\Volt\Component;

new class extends Component {
    public $studentId = null;
    public $student_id = '';
    public $program_id = '';
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $date_of_birth = '';
    public $gender = 'male';
    public $address = '';
    public $guardian_name = '';
    public $guardian_phone = '';
    public $guardian_email = '';
    public $enrollment_date = '';
    public $status = 'active';
    public $class_level = '';
    public $notes = '';

    public function mount($id = null)
    {
        if ($id) {
            $this->studentId = $id;
            $student = Student::findOrFail($id);
            
            $this->student_id = $student->student_id;
            $this->program_id = $student->program_id;
            $this->first_name = $student->first_name;
            $this->last_name = $student->last_name;
            $this->email = $student->email;
            $this->phone = $student->phone;
            $this->date_of_birth = $student->date_of_birth?->format('Y-m-d');
            $this->gender = $student->gender;
            $this->address = $student->address;
            $this->guardian_name = $student->guardian_name;
            $this->guardian_phone = $student->guardian_phone;
            $this->guardian_email = $student->guardian_email;
            $this->enrollment_date = $student->enrollment_date?->format('Y-m-d');
            $this->status = $student->status;
            $this->class_level = $student->class_level;
            $this->notes = $student->notes;
        } else {
            $this->enrollment_date = now()->format('Y-m-d');
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'program_id' => 'required|exists:programs,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'address' => 'nullable|string',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,graduated,suspended,withdrawn',
            'class_level' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($this->studentId) {
            $student = Student::findOrFail($this->studentId);
            $student->update($validated);
            session()->flash('message', 'Student updated successfully.');
        } else {
            $student = Student::create($validated);
            session()->flash('message', 'Student created successfully.');
        }

        return redirect()->route('students.show', $student->id);
    }

    public function with(): array
    {
        return [
            'programs' => Program::all(),
            'isEdit' => $this->studentId !== null,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $isEdit ? 'Edit Student' : 'New Student' }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $isEdit ? 'Update student information' : 'Register a new student' }}
                </p>
            </div>
            <a href="{{ route('students.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Basic Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($isEdit)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student ID</label>
                            <input type="text" 
                                   value="{{ $student_id }}"
                                   disabled
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-500">
                        </div>
                    @endif

                    <div class="{{ $isEdit ? '' : 'md:col-span-2' }}">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                        <select wire:model="program_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('program_id') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                        <input type="text" 
                               wire:model="first_name"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('first_name') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
                        <input type="text" 
                               wire:model="last_name"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('last_name') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                        <input type="email" 
                               wire:model="email"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('email') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone *</label>
                        <input type="text" 
                               wire:model="phone"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('phone') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth *</label>
                        <input type="date" 
                               wire:model="date_of_birth"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('date_of_birth') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender *</label>
                        <select wire:model="gender"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        @error('gender') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea wire:model="address"
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        @error('address') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Guardian Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Guardian Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Name *</label>
                        <input type="text" 
                               wire:model="guardian_name"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('guardian_name') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Phone *</label>
                        <input type="text" 
                               wire:model="guardian_phone"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('guardian_phone') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Email</label>
                        <input type="email" 
                               wire:model="guardian_email"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('guardian_email') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Enrollment Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Enrollment Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enrollment Date *</label>
                        <input type="date" 
                               wire:model="enrollment_date"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('enrollment_date') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                        <select wire:model="status"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="active">Active</option>
                            <option value="graduated">Graduated</option>
                            <option value="suspended">Suspended</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                        @error('status') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class Level</label>
                        <input type="text" 
                               wire:model="class_level"
                               placeholder="e.g., Year 1, Grade 10"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('class_level') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes"
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        @error('notes') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('students.index') }}" 
                   class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    {{ $isEdit ? 'Update Student' : 'Create Student' }}
                </button>
            </div>
        </form>
    </div>
</div>
