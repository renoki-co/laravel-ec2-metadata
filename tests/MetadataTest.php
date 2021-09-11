<?php

namespace RenokiCo\Ec2Metadata\Test;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RenokiCo\Ec2Metadata\Ec2Metadata;
use RenokiCo\Ec2Metadata\Exceptions\IsNotInterruptingException;

class MetadataTest extends TestCase
{
    public function test_ami()
    {
        Http::fake([
            'http://169.254.169.254/*' => Http::sequence()
                ->push('some-token', 200)
                ->push('ami-1234', 200),
        ]);

        $this->assertEquals('ami-1234', Ec2Metadata::ami());

        Http::assertSentInOrder([
            function (Request $request) {
                return $request->method() === 'PUT' &&
                    $request->url() === 'http://169.254.169.254/latest/api/token' &&
                    $request->header('X-AWS-EC2-Metadata-Token-TTL-Seconds') === ['21600'];
            },
            function (Request $request) {
                return $request->method() === 'GET' &&
                    $request->url() === 'http://169.254.169.254/latest/meta-data/ami-id' &&
                    $request->header('X-AWS-EC2-Metadata-Token') === ['some-token'];
            },
        ]);
    }

    public function test_macro()
    {
        Http::fake([
            'http://169.254.169.254/*' => Http::sequence()
                ->push('some-token', 200)
                ->push('kernel-123', 200),
        ]);

        Ec2Metadata::macro('kernelId', function () {
            /** @var \RenokiCo\Ec2Metadata\Ec2Metadata $this */
            return static::get('kernel-id');
        });

        $this->assertEquals('kernel-123', Ec2Metadata::kernelId());
    }

    public function test_termination_notice_is_unavailable()
    {
        Http::fake([
            'http://169.254.169.254/*' => Http::sequence()
                ->push('some-token', 200)
                ->push($message = ['action' => 'terminate', 'time' => '2017-09-18T08:22:00Z'], 200)
                ->push('Not found', 404),
        ]);

        $this->assertEquals($message, Ec2Metadata::terminationNotice());
        $this->assertNull(Ec2Metadata::terminationNotice());
    }
}
