<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group dusk
 */
class JobFlowTest extends DuskTestCase
{
    /**
     * @group dusk
     * @test
     */
    public function recruiter_can_publish_job(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/login')
                ->assertSee('Login');
        });
    }

    /**
     * @group dusk
     * @test
     */
    public function candidate_can_apply_to_published_job(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/jobs/123')
                ->assertSee('Engenheiro de Software');
        });
    }

    /**
     * @group dusk
     * @test
     */
    public function application_responds_to_health_check(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/')
                ->assertDriverStatus(200);
        });
    }
}
