@props([
    'transaction',
    'modalName' => 'lending-request-'.$transaction->id,
    'canRequestLending' => false,
    'lendingRequestBlockReason' => null,
])

@permission('lending-requests.request')
    @if ($canShowLendingButton ?? false)
        <button type="button" class="btn btn-secondary" data-modal-open="{{ $modalName }}">
            {{ __('lending_requests.request_button') }}
        </button>

        <x-modal :name="$modalName">
            <div class="card" style="margin:0;">
                <div class="card-header">
                    <h3 class="card-title">{{ __('lending_requests.request_modal_title') }}</h3>
                </div>
                @if ($canRequestLending)
                    <form method="POST" action="{{ route('lending-requests.store') }}">
                        @csrf
                        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                        <div class="card-body">
                            <p>{{ __('lending_requests.request_modal_body', ['reference' => $transaction->reference_number]) }}</p>
                            <div class="form-group">
                                <x-input-label for="purpose-{{ $transaction->id }}" :value="__('lending_requests.purpose')" />
                                <textarea id="purpose-{{ $transaction->id }}" name="purpose" class="form-textarea" rows="3" placeholder="{{ __('lending_requests.purpose_placeholder') }}">{{ old('transaction_id') == $transaction->id ? old('purpose') : '' }}</textarea>
                                <x-input-error :messages="$errors->get('purpose')" />
                            </div>
                            <div class="form-group">
                                <x-input-label for="due_date-{{ $transaction->id }}" :value="__('lending_requests.due_date')" />
                                <x-text-input id="due_date-{{ $transaction->id }}" name="due_date" type="date" :value="old('transaction_id') == $transaction->id ? old('due_date') : ''" />
                                <x-input-error :messages="$errors->get('due_date')" />
                            </div>
                        </div>
                        <div class="card-footer form-actions">
                            <x-primary-button>{{ __('lending_requests.confirm_request') }}</x-primary-button>
                            <button type="button" class="btn btn-secondary" data-modal-close>{{ __('common.cancel') }}</button>
                        </div>
                    </form>
                @else
                    <div class="card-body">
                        <p class="text-muted">{{ $lendingRequestBlockReason ?? __('lending_requests.not_eligible_hint') }}</p>
                    </div>
                    <div class="card-footer form-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>{{ __('common.cancel') }}</button>
                    </div>
                @endif
            </div>
        </x-modal>
    @endif
@endpermission
