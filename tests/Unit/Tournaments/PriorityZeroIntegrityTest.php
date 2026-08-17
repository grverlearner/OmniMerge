<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Models\User;
use App\Policies\PhaseTemplatePolicy;
use App\Policies\TournamentTemplatePolicy;
use App\Services\Tournaments\CompetitionLab\Engines\GroupStageLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use App\Services\Tournaments\CompetitionLab\Engines\RoundRobinLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\SwissLabEngine;
use Mockery;
use PHPUnit\Framework\TestCase;

class PriorityZeroIntegrityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_external_tournament_clone_respects_allow_cloning(): void
    {
        $owner = new User();
        $owner->id = 10;

        $viewer = new User();
        $viewer->id = 20;

        $template = new TournamentTemplate();
        $template->user_id = $owner->id;
        $template->status = 'ACTIVE';
        $template->visibility = 'PUBLIC';
        $template->published_at = new \DateTimeImmutable();
        $template->allow_cloning = false;

        $policy = new TournamentTemplatePolicy();

        $this->assertTrue(
            $policy->duplicate($owner, $template)
        );

        $this->assertFalse(
            $policy->duplicate($viewer, $template)
        );

        $template->allow_cloning = true;

        $this->assertTrue(
            $policy->duplicate($viewer, $template)
        );
    }

    public function test_external_phase_clone_respects_allow_cloning(): void
    {
        $owner = new User();
        $owner->id = 10;

        $viewer = new User();
        $viewer->id = 20;

        $phase = new PhaseTemplate();
        $phase->user_id = $owner->id;
        $phase->status = 'ACTIVE';
        $phase->visibility = 'PUBLIC';
        $phase->published_at = new \DateTimeImmutable();
        $phase->allow_cloning = false;

        $policy = new PhaseTemplatePolicy();

        $this->assertTrue(
            $policy->duplicate($owner, $phase)
        );

        $this->assertFalse(
            $policy->duplicate($viewer, $phase)
        );

        $phase->allow_cloning = true;

        $this->assertTrue(
            $policy->duplicate($viewer, $phase)
        );
    }

    public function test_engine_manager_reports_supported_phase_types(): void
    {
        $single = Mockery::mock(SingleEliminationLabEngine::class);
        $roundRobin = Mockery::mock(RoundRobinLabEngine::class);
        $groupStage = Mockery::mock(GroupStageLabEngine::class);
        $swiss = Mockery::mock(SwissLabEngine::class);

        $single->shouldReceive('supports')
            ->andReturnUsing(
                fn(string $type): bool =>
                $type === 'SINGLE_ELIMINATION'
            );

        $roundRobin->shouldReceive('supports')
            ->andReturnUsing(
                fn(string $type): bool =>
                $type === 'ROUND_ROBIN'
            );

        $groupStage->shouldReceive('supports')
            ->andReturnUsing(
                fn(string $type): bool =>
                $type === 'GROUP_STAGE'
            );

        $swiss->shouldReceive('supports')
            ->andReturnUsing(
                fn(string $type): bool =>
                $type === 'SWISS'
            );

        $manager = new LabPhaseEngineManager(
            $single,
            $roundRobin,
            $groupStage,
            $swiss
        );

        $this->assertTrue(
            $manager->supports('SINGLE_ELIMINATION')
        );
        $this->assertTrue(
            $manager->supports('ROUND_ROBIN')
        );
        $this->assertTrue(
            $manager->supports('GROUP_STAGE')
        );
        $this->assertTrue(
            $manager->supports('SWISS')
        );
        $this->assertFalse(
            $manager->supports('LEAGUE')
        );
        $this->assertFalse(
            $manager->supports('CUSTOM')
        );
    }
}
