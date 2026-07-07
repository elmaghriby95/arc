@props([
    'lendingRequest',
    'canReview' => false,
    'canHandover' => false,
    'canReturn' => false,
])

@if ($canReview || $canHandover || $canReturn)
    <div class="lending-sidebar-card">
        <div class="lending-sidebar-card-header">
            <h3 class="lending-sidebar-card-title">{{ __('lending_requests.pending_actions') }}</h3>
            <p class="lending-sidebar-card-subtitle">{{ __('lending_requests.pending_actions_subtitle') }}</p>
        </div>
        <div class="lending-sidebar-card-body">
            @if ($canReview)
                <div class="lending-action-block">
                    <h4 class="lending-action-block-title">{{ __('lending_requests.approve_review') }}</h4>
                    <form method="POST" action="{{ route('lending-requests.review', $lendingRequest) }}">
                        @csrf
                        <input type="hidden" name="approve" value="1">
                        <div class="form-group">
                            <x-input-label for="review-notes-approve" :value="__('lending_requests.review_notes')" />
                            <textarea id="review-notes-approve" name="notes" class="form-textarea" rows="2" placeholder="{{ __('lending_requests.review_notes_placeholder') }}"></textarea>
                        </div>
                        <x-primary-button>{{ __('lending_requests.approve_review') }}</x-primary-button>
                    </form>
                </div>
                <div class="lending-action-block lending-action-block--danger">
                    <h4 class="lending-action-block-title">{{ __('lending_requests.reject_review') }}</h4>
                    <form method="POST" action="{{ route('lending-requests.review', $lendingRequest) }}">
                        @csrf
                        <input type="hidden" name="approve" value="0">
                        <div class="form-group">
                            <x-input-label for="review-notes-reject" :value="__('lending_requests.reject_notes_required')" />
                            <textarea id="review-notes-reject" name="notes" class="form-textarea" rows="2" required placeholder="{{ __('lending_requests.reject_notes_placeholder') }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">{{ __('lending_requests.reject_review') }}</button>
                    </form>
                </div>
            @endif

            @if ($canHandover)
                <div class="lending-action-block">
                    <h4 class="lending-action-block-title">{{ __('lending_requests.confirm_handover') }}</h4>
                    <form method="POST" action="{{ route('lending-requests.handover', $lendingRequest) }}">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <div class="form-group">
                            <x-input-label for="handover-notes-confirm" :value="__('lending_requests.handover_notes')" />
                            <textarea id="handover-notes-confirm" name="notes" class="form-textarea" rows="2" placeholder="{{ __('lending_requests.handover_notes_placeholder') }}"></textarea>
                        </div>
                        <x-primary-button>{{ __('lending_requests.confirm_handover') }}</x-primary-button>
                    </form>
                </div>
                <div class="lending-action-block lending-action-block--danger">
                    <h4 class="lending-action-block-title">{{ __('lending_requests.reject_handover') }}</h4>
                    <form method="POST" action="{{ route('lending-requests.handover', $lendingRequest) }}">
                        @csrf
                        <input type="hidden" name="confirm" value="0">
                        <div class="form-group">
                            <x-input-label for="handover-notes-reject" :value="__('lending_requests.reject_notes_required')" />
                            <textarea id="handover-notes-reject" name="notes" class="form-textarea" rows="2" required placeholder="{{ __('lending_requests.reject_notes_placeholder') }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">{{ __('lending_requests.reject_handover') }}</button>
                    </form>
                </div>
            @endif

            @if ($canReturn)
                <div class="lending-action-block">
                    <h4 class="lending-action-block-title">{{ __('lending_requests.return_documents') }}</h4>
                    <form method="POST" action="{{ route('lending-requests.return', $lendingRequest) }}">
                        @csrf
                        <div class="form-group">
                            <x-input-label for="return-notes" :value="__('lending_requests.return_notes')" />
                            <textarea id="return-notes" name="notes" class="form-textarea" rows="2" placeholder="{{ __('lending_requests.return_notes_placeholder') }}"></textarea>
                        </div>
                        <x-primary-button>{{ __('lending_requests.return_documents') }}</x-primary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
