<?php

namespace OSS\Tests;


use OSS\Model\WebsiteConfig;
use OSS\OssClient;

class WebsiteConfigTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
<?xml version="1.0" encoding="utf-8"?>
<WebsiteConfiguration>
<IndexDocument>
<Suffix>index.html</Suffix>
</IndexDocument>
<ErrorDocument>
<Key>errorDocument.html</Key>
</ErrorDocument>
</WebsiteConfiguration>
BBBB;

    private $nullXml = <<<BBBB
<?xml version="1.0" encoding="utf-8"?><WebsiteConfiguration><IndexDocument><Suffix/></IndexDocument><ErrorDocument><Key/></ErrorDocument></WebsiteConfiguration>
BBBB;
    private $nullXml2 = <<<BBBB
<?xml version="1.0" encoding="utf-8"?><WebsiteConfiguration><IndexDocument><Suffix></Suffix></IndexDocument><ErrorDocument><Key></Key></ErrorDocument></WebsiteConfiguration>
BBBB;

    public function testParseValidXml()
    {
        $websiteConfig = new WebsiteConfig("index");
        $websiteConfig->parseFromXml($this->validXml);
        $this->assertEquals($this->cleanXml($this->validXml), $this->cleanXml($websiteConfig->serializeToXml()));
    }

    public function testParsenullXml()
    {
        $websiteConfig = new WebsiteConfig();
        $websiteConfig->parseFromXml($this->nullXml);
        $this->assertTrue($this->cleanXml($this->nullXml) === $this->cleanXml($websiteConfig->serializeToXml()) ||
            $this->cleanXml($this->nullXml2) === $this->cleanXml($websiteConfig->serializeToXml()));
    }

    public function testWebsiteConstruct()
    {
        $websiteConfig = new WebsiteConfig("index.html", "errorDocument.html");
        $this->assertEquals('index.html', $websiteConfig->getIndexDocument());
        $this->assertEquals('errorDocument.html', $websiteConfig->getErrorDocument());
        $this->assertEquals($this->cleanXml($this->validXml), $this->cleanXml($websiteConfig->serializeToXml()));
    }

    private function cleanXml($xml)
    {
        return str_replace("\n", "", str_replace("\r", "", $xml));
    }

    public static function tearDownAfterClass()
    {
        $accessKeyId = ' ' . getenv('OSS_ACCESS_KEY_ID') . ' ';
        $accessKeySecret = ' ' . getenv('OSS_ACCESS_KEY_SECRET') . ' ';
        $endpoint = ' ' . getenv('OSS_ENDPOINT') . '/ ';
        $bucket = getenv('BUCKET_NAME_PREFIX').'-'.getenv('OSS_BUCKET');

        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);

        if (!$ossClient->doesBucketExist($bucket)) {
            return;
        }

        $objects = $ossClient->listObjects($bucket, array('max-keys' => 1000, 'delimiter' => ''))->getObjectList();
        $keys = array();
        foreach ($objects as $obj) {
            $keys[] = $obj->getKey();
        }
        if (count($keys) > 0) {
            $ossClient->deleteObjects($bucket, $keys);
        }
        $uploads = $ossClient->listMultipartUploads($bucket)->getUploads();
        foreach ($uploads as $up) {
            $ossClient->abortMultipartUpload($bucket, $up->getKey(), $up->getUploadId());
        }

        $ossClient->deleteBucket($bucket);
    }
}
