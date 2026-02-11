<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AiChatSession;
use App\Services\AiAssistantService;
use Illuminate\Support\Facades\Auth;

class AiAssistant extends Component
{
    public $session;
    public $message = '';

    public function mount($sessionId = null)
    {
        if ($sessionId) {
            $this->session = AiChatSession::where('user_id', Auth::id())->findOrFail($sessionId);
        } else {
            // Get most recent session or create new
            $this->session = AiChatSession::where('user_id', Auth::id())
                ->latest('last_active_at')
                ->first();

            if (!$this->session) {
                $this->session = AiChatSession::create([
                    'user_id' => Auth::id(),
                    'title' => 'New Accounting Chat',
                    'last_active_at' => now(),
                ]);
            }
        }
    }

    public function sendMessage(AiAssistantService $aiService)
    {
        if (trim($this->message) === '') return;

        $userMsg = $this->message;
        $this->message = ''; // Clear input immediately
        
        // Step 1: Record user message (Fast)
        $aiService->addUserMessage($this->session, $userMsg);

        // Step 2: Trigger the AI analysis in a separate request (Optimistic UI)
        $this->dispatch('trigger-analysis');
        $this->dispatch('messageSent'); // Scroll to bottom
    }

    public function generateResponse(AiAssistantService $aiService)
    {
        // Step 3: Perform the heavy AI analysis
        $aiService->generateAnalysis($this->session);
        
        $this->dispatch('messageSent'); // Scroll to bottom after AI replies
    }

    public function clearChat()
    {
        $this->session->messages()->delete();
        $this->session->update(['title' => 'New Accounting Chat']);
    }

    public function render()
    {
        return view('livewire.ai-assistant', [
            'messages' => $this->session->messages()->orderBy('created_at', 'asc')->get(),
        ]);
    }
}
