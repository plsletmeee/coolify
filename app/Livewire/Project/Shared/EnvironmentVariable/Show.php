<?php

namespace App\Livewire\Project\Shared\EnvironmentVariable;

use App\Models\EnvironmentVariable as ModelsEnvironmentVariable;
use App\Models\SharedEnvironmentVariable;
use Livewire\Component;
use Visus\Cuid2\Cuid2;

class Show extends Component
{
    public $parameters;
    public ModelsEnvironmentVariable|SharedEnvironmentVariable $env;
    public ?string $modalId = null;
    public bool $isDisabled = false;
    public bool $isLocked = false;
    public bool $isSharedVariable = false;
    public string $type;

    protected $rules = [
        'env.key' => 'required|string',
        'env.value' => 'nullable',
        'env.is_build_time' => 'required|boolean',
        'env.is_shown_once' => 'required|boolean',
        'env.real_value' => 'nullable',
    ];
    protected $validationAttributes = [
        'env.key' => 'Key',
        'env.value' => 'Value',
        'env.is_build_time' => 'Build Time',
        'env.is_shown_once' => 'Shown Once',
    ];

    public function mount()
    {
        if ($this->env->getMorphClass() === 'App\Models\SharedEnvironmentVariable') {
            $this->isSharedVariable = true;
        }
        $this->modalId = new Cuid2(7);
        $this->parameters = get_route_parameters();
        $this->checkEnvs();
    }
    public function checkEnvs()
    {
        $this->isDisabled = false;
        if (str($this->env->key)->startsWith('SERVICE_FQDN') || str($this->env->key)->startsWith('SERVICE_URL')) {
            $this->isDisabled = true;
        }
        if ($this->env->is_shown_once) {
            $this->isLocked = true;
        }
    }
    public function serialize() {
        data_forget($this->env, 'real_value');
        if ($this->env->getMorphClass() === 'App\Models\SharedEnvironmentVariable') {
            data_forget($this->env, 'is_build_time');
        }
    }
    public function lock()
    {
        $this->env->is_shown_once = true;
        $this->serialize();
        $this->env->save();
        $this->checkEnvs();
        $this->dispatch('refreshEnvs');
    }
    public function instantSave()
    {
        $this->submit();
    }
    public function submit()
    {
        try {
            if ($this->isSharedVariable) {
                $this->validate([
                    'env.key' => 'required|string',
                    'env.value' => 'nullable',
                    'env.is_shown_once' => 'required|boolean',
                ]);
            } else {
                $this->validate();
            }
            $this->serialize();
            $this->env->save();
            $this->dispatch('success', 'Environment variable updated successfully.');
            $this->dispatch('refreshEnvs');
        } catch(\Exception $e) {
            return handleError($e);
        }
    }

    public function delete()
    {
        try {
            $this->env->delete();
            $this->dispatch('refreshEnvs');
        } catch (\Exception $e) {
            return handleError($e);
        }
    }
}
