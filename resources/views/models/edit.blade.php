@extends('layouts.app')
@section('title', $model->exists ? 'Edit Model' : 'New Model')
@section('header_title', $model->exists ? 'Edit Model' : 'New Model')

@section('content')
  @if (session('ok'))
    <div class="card" style="padding:12px;margin-bottom:12px;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;">
      {{ session('ok') }}
    </div>
  @endif

  <form method="post" action="{{ $model->exists ? route('models.update',$model) : route('models.store') }}">
    @csrf
    @if($model->exists) @method('PUT') @endif

    <section class="card" style="padding:16px">
      <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="bold">Name</label>
          <input name="name" value="{{ old('name',$model->name) }}" class="table" style="width:100%;padding:8px" required>
        </div>
        <div>
          <label class="bold">Wallet</label>
          <input name="wallet" value="{{ old('wallet',$model->wallet) }}" class="table" style="width:100%;padding:8px">
        </div>

        <div>
          <label class="bold">Equity ($)</label>
          <input name="equity" type="number" step="0.01" value="{{ old('equity',$model->equity) }}" class="table" style="width:100%;padding:8px">
        </div>
        <div>
          <label class="bold">Return %</label>
          <input name="return_pct" type="number" step="0.01" value="{{ old('return_pct',$model->return_pct) }}" class="table" style="width:100%;padding:8px">
        </div>

        <div>
          <label class="bold">Check Interval (minutes)</label>
          <input name="check_interval_min" type="number" min="1" value="{{ old('check_interval_min',$model->check_interval_min ?? 60) }}" class="table" style="width:100%;padding:8px" required>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <label class="bold">Active</label>
          <input type="checkbox" name="active" value="1" {{ old('active',$model->active) ? 'checked' : '' }}>
        </div>
      </div>
    </section>


    <section class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">

      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Pre-Market Prompt</div>
        <textarea id="premarket_prompt" name="premarket_prompt" rows="10" class="table" {{ isset($model->id) ? ($model->premarket_prompt_status == 1 ? '' : 'disabled') : '' }} style="width:100%;padding:8px">{{ old('premarket_prompt',$model->premarket_prompt) }}</textarea>
        <div class="small" style="margin-top:6px">
          This is the <b>big planning prompt</b> that runs before the market opens and builds today&apos;s strategy playbook based on news, trends, funding, and your risk rules.
        </div>

        <div style="display:flex;justify-content: flex-end; align-items:center;gap:8px; margin-top: 10px;">                    
          <button type="button"
                  onclick="togglePromptStatus(this)"
                  class="prompt-status btn btn-sm btn-prompt"
                  data-id="{{ $model->id }}"
                  data-name="Pre-Market Prompt"
                  data-type="premarket"
                  data-status="{{ $model->premarket_prompt_status }}"> 
              {{ $model->premarket_prompt_status == 1 ? 'Disable' : 'Enable' }}
          </button>          
        </div>
        <div id="prompt_notification_premarket" style="display: inline-flex; color: red;"></div>
      </div>

      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Pre-Market &amp; Loop Settings</div>
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="bold">Pre-Market Run Time (HH:MM)</label>
            <input name="premarket_run_time" value="{{ old('premarket_run_time',$model->premarket_run_time) }}" class="table" style="width:100%;padding:8px" placeholder="09:00">
            <div class="small text-dim">When the scheduler should run the pre-market plan (app timezone).</div>
          </div>
          <div>
            <label class="bold">Max Strategies per Day</label>
            <input name="max_strategies_per_day" type="number" min="0" step="1" value="{{ old('max_strategies_per_day',$model->max_strategies_per_day) }}" class="table" style="width:100%;padding:8px" placeholder="6">
          </div>
          <div>
            <label class="bold">Max Symbols per Day</label>
            <input name="max_symbols_per_day" type="number" min="0" step="1" value="{{ old('max_symbols_per_day',$model->max_symbols_per_day) }}" class="table" style="width:100%;padding:8px" placeholder="5">
          </div>
          <div>
            <label class="bold">Default Risk per Strategy (%)</label>
            <input name="default_risk_per_strategy_pct" type="number" min="0" max="100" step="0.1" value="{{ old('default_risk_per_strategy_pct',$model->default_risk_per_strategy_pct) }}" class="table" style="width:100%;padding:8px" placeholder="1.0">
          </div>
          <div>
            <label class="bold">Allow Sleeper Strategies</label>
            <select name="allow_sleeper_strategies" class="table" style="width:100%;padding:8px" required>
              <option value="" {{ old('allow_sleeper_strategies',$model->allow_sleeper_strategies) === null ? 'selected' : '' }}>Use default</option>
              <option value="1" {{ old('allow_sleeper_strategies',$model->allow_sleeper_strategies) ? 'selected' : '' }}>Yes</option>
              <option value="0" {{ old('allow_sleeper_strategies',$model->allow_sleeper_strategies) === 0 ? 'selected' : '' }}>No</option>
            </select>
          </div>
          <div>
            <label class="bold">Allow Activating Sleepers (Loop)</label>
            <select name="allow_activate_sleepers" class="table" style="width:100%;padding:8px" required>
              <option value="" {{ old('allow_activate_sleepers',$model->allow_activate_sleepers) === null ? 'selected' : '' }}>Use default</option>
              <option value="1" {{ old('allow_activate_sleepers',$model->allow_activate_sleepers) ? 'selected' : '' }}>Yes</option>
              <option value="0" {{ old('allow_activate_sleepers',$model->allow_activate_sleepers) === 0 ? 'selected' : '' }}>No</option>
            </select>
          </div>
          <div>
            <label class="bold">Allow Early Exit on Invalidation</label>
            <select name="allow_early_exit_on_invalidation" class="table" style="width:100%;padding:8px" required>
              <option value="" {{ old('allow_early_exit_on_invalidation',$model->allow_early_exit_on_invalidation) === null ? 'selected' : '' }}>Use default</option>
              <option value="1" {{ old('allow_early_exit_on_invalidation',$model->allow_early_exit_on_invalidation) ? 'selected' : '' }}>Yes</option>
              <option value="0" {{ old('allow_early_exit_on_invalidation',$model->allow_early_exit_on_invalidation) === 0 ? 'selected' : '' }}>No</option>
            </select>
          </div>
          <div>
            <label class="bold">Max Adds per Position</label>
            <input name="max_adds_per_position" type="number" min="0" max="10" step="1" value="{{ old('max_adds_per_position',$model->max_adds_per_position) }}" class="table" style="width:100%;padding:8px" placeholder="0" required>
          </div>
          <div>
            <label class="bold">Min Price Move to Trigger Loop (%)</label>
            <input name="loop_min_price_move_pct" type="number" min="0" max="100" step="0.01" value="{{ old('loop_min_price_move_pct',$model->loop_min_price_move_pct) }}" class="table" style="width:100%;padding:8px" placeholder="0.30">
            <div class="small text-dim">Optional: if price change since last decision is smaller than this, you can skip the AI call to save tokens.</div>
          </div>
        </div>
      </div>

    </section>

    <section class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Start Prompt</div>
        <textarea id="start_prompt" name="start_prompt" rows="16" class="table" {{ isset($model->id) ? ($model->start_prompt_status == 1 ? '' : 'disabled') : '' }}  style="width:100%;padding:8px">{{ old('start_prompt',$model->start_prompt) }}</textarea>
        <div class="small" style="margin-top:6px">Used once at boot/start to initialize the model’s policy and state.</div>

        <div style="display:flex;justify-content: flex-end; align-items:center;gap:8px; margin-top: 10px;">                    
          <button type="button"
                  onclick="togglePromptStatus(this)"
                  class="prompt-status btn btn-sm btn-prompt"
                  data-id="{{ $model->id }}"
                  data-name="Start Prompt"
                  data-type="start"
                  data-status="{{ $model->start_prompt_status }}"> 
              {{ $model->start_prompt_status == 1 ? 'Disable' : 'Enable' }}
          </button>          
        </div>
        <div id="prompt_notification_start" style="display: inline-flex; color: red;"></div>
      </div>

      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Loop / Check Prompt</div>
        <textarea id="loop_prompt" name="loop_prompt" rows="16" class="table" {{ isset($model->id) ? ($model->loop_prompt_status == 1 ? '' : 'disabled') : '' }}  style="width:100%;padding:8px">{{ old('loop_prompt',$model->loop_prompt) }}</textarea>
        <div class="small" style="margin-top:6px">
          Executed every <b>{{ old('check_interval_min',$model->check_interval_min ?? 60) }}</b> min.
          Should output an action + short reasoning. (e.g. HOLD / CLOSE / OPEN)
        </div>

        <div style="display:flex;justify-content: flex-end; align-items:center;gap:8px; margin-top: 10px;">                    
          <button type="button"
                  onclick="togglePromptStatus(this)"
                  class="prompt-status btn btn-sm btn-prompt"
                  data-id="{{ $model->id }}"
                  data-name="Loop / Check Prompt"
                  data-type="loop"
                  data-status="{{ $model->loop_prompt_status }}"> 
              {{ $model->loop_prompt_status == 1 ? 'Disable' : 'Enable' }}
          </button>          
        </div>
        <div id="prompt_notification_loop" style="display: inline-flex; color: red;"></div>
      </div>
    </section>

    <div style="margin-top:16px;display:flex;gap:12px">
      <button class="tab active" type="submit">Save</button>
      <a class="tab" href="{{ route('models.index') }}">Back</a>
    </div>
  </form>
@endsection

@section('scripts')
<script>
  // This will now work because jQuery is loaded first
  console.log("jQuery version:", $.fn.jquery);

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

  function togglePromptStatus(button) {
      var btn = $(button);
      var id = btn.data('id');
      var name = btn.data('name');
      var type = btn.data('type');
      var currentStatus = btn.data('status');

      if(id)
      {
        $.ajax({
            url: '/toggle-prompt-status/' + id,
            type: 'POST',
            data: {
                status: currentStatus,
                prompt_name: name
            },
            success: function (response) {

                // Update button text
                if (response.new_status == 1)
                {
                  btn.text('Disable');
                  $("#" + type + "_prompt").removeAttr('disabled');
                  $('#prompt_notification_' + type).css('color', 'green');
                }
                else
                {
                  btn.text('Enable');
                  $("#" + type + "_prompt").attr('disabled', 'disabled');
                  $('#prompt_notification_' + type).css('color', 'red');
                }
               
                // Update data-status
                btn.data('status', response.new_status);

                // Optional: update status column in the table
                $('#prompt_notification_' + type).html(
                    name + ' is ' + (response.new_status ? 'Enabled' : 'Disabled')
                );
            },

            error: function () {  
              $('#prompt_notification_' + type).css('color', 'red');            
              $('#prompt_notification_' + type).html('Error updating '+ name +' status.');
            }
        });
      }
  }
</script>
@endsection
