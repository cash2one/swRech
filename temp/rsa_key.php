<?php
class rsa
{
/**格式化公钥
 * $pubKey PKCS#1格式的公钥串
 * return pem格式公钥， 可以保存为.pem文件
 */
public	static	function formatPubKey($pubKey)
{
    $fKey = "-----BEGIN PUBLIC KEY-----\n";
    $len = strlen($pubKey);
    for($i = 0; $i < $len; )
	{
        $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PUBLIC KEY-----";
    return $fKey;
}


/**格式化公钥
 * $priKey PKCS#1格式的私钥串
 * return pem格式私钥， 可以保存为.pem文件
 */
public	static	function formatPriKey($priKey)
{
    $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
    $len = strlen($priKey);
    for($i = 0; $i < $len; )
	{
        $fKey = $fKey . substr($priKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END RSA PRIVATE KEY-----";
    return $fKey;
}

}

$target_dir = __DIR__.'/../rsa/';

$privkey = array
(
	'lenovo'=>'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAPqEsyvOZLGk7l7PVkb/Rl1wsoR7MLOcrrg71gw0VQH+Cvn9LdkyK3FXDgLt4EYlYMxztorsodZ0hpSwth0v3gqD8JKNR4RNF36hU7EttkrX5SJvjVaD1z00G+PqmxYDZSwnce8IlHv+dhBAnC1O2Y1znTSdUbI9YHVHf98+7tpRAgMBAAECgYA7jih6FYrioG76oxfDsRQtSoll7VNan7j5m1/7bsV2v8okPAgWRlMFksiF8b8Lbf9hG3Ed/btLnFASMSBaN/L03JhP3VNp1JWtQa/aS+O1E6fbHfH5PmnIYOKcxyzBA5GolM/KlNQKhwG9Y7IVFxKQSjkXHmoyTiZw4XyEIQ4YAQJBAP10nM9SuUInKNArjQJklxrfoxRHXiJQTZnwEhvSoHOl5A8UYOeGnkKIb040Nm12MQw14cChbGKyBcBfV91u8PECQQD9CIns9i/uWz1xgh6WOKUXk1pS17JPu0Xr4mCIoQVc9p6uNoY7Xe4dhqFy+TRQiQgyaAZM3CpgF2o0jZs0Pn9hAkA4MVx60rpkIk54KM+wkiC2QpLjchc6wFcUJBe/t2j/eu4fMcNyUXRRB/K1gfn3NtcU//U3QhLHpvSDfB/85hXhAkEAv45NTFT76EiudVX6beMoKHbnNDwSw4WU44SXbBfqhXw2mNCyQhNUBoo7g1zhm+6BhBd4XYt3kRNo1aw2SRDfQQJAC0O11bJ6vz29KDvJk1jI/fsitnJs5+tcm2qYQHoBhSas44bz8c225Y6BR0O7MNOYzaNIRt7x2O9fXLOgakDr9A==',
	'anfeng'=>'MIICXAIBAAKBgQCmqfTs69lZIwit/SoPoF4TKqZaS/ehfPjLlUkRFhUKMzsunsWW5npHnwiq16EpCnV+gNWNBbYBUYrusqT6/ktiDoi0cGHfBHOSG91EYLnsatov2iMQ2Xx/AeYLMr0Ci650BMOW4MFf7orJv4Hweyy8GYUSixzLlzuORJw5+RcpQwIDAQABAoGBAIri2v6M1IVZkqQbcu+uvnbOde6NnADxOgu9jjQ06LelVc5V9Wb5DjGdMAXex6iB1MPk7REmDzQuFc8xBBc9zubBd75yoDgdGHlSThqPOaS5iHW/coz8G3MhlSdL 8DsX45FF6u6DFq/7GUPXcyYZ/5nehy+SlXT6GSrun4CUom8RAkEA2LtSx0C8S31Ud8WqLbaos8OGJOMS0ZPVp4id7P98aUupl1jnALw6xQ6AZXlPfvLODZbzoGR1qZM1d69+3e0uBQJBAMTcVbcffdcZcQfL+v+D+1+AtwoekZ3bq+hRcQ4EqkYhFgnMWkEQM9gYpVWwQP/zEzoPHEsBMwcZsZwZKYXa1KcCQFwM+7SLJy481fJk7smpqe9n3QKvux9uVbFpUgIF5RZnv2j7pmlmiOOHLDttEbmOcLvO4DzATkUus+fYjt69TO0CQHE81MlrgfVzGwODIDROdRAweBmID/cf3zjZcBDCOjfw3D5yoBABA/Fv69rocdtItWyWOI4keHfDHdlO2AW5JM0CQEO6ebCJh5HAc3XyNd0RzWPCKc+TZKmowD+SottEeY0saj2eLTM24rQ4lCMLm9ICJ8oLm/OlnlUqE6++FdxEVKw=',
	'huawei'=>'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCvrflRzUkVtoZ2YQ7J1lEiLvkvRqjQMgaeZ0sO1lePb4CZg3qdxHGfHXfEru/XFXvFp/ZOBVV9kDOSmIYl8UF7BzV92MWFaunP1w+zvF8skvPpeTQ1L/LlKGLIqcMh+BaSJQLHEtdcSYMeZctEbvtoI81qIxC73okEBv0wD9XFg2jG2JJa19vtNOBhpjz8QmJ/IOjlLhnFH6agtVUTQy65+HOQoGwRZdSiLKG9Yu17W9yckepCpui7mqMqhYJqXl+QWjRW6BvO9S0pD2APvQFF47Fr6TEh5RNY9FeWhTOXLW/z4MBC31yCNbodchu5S/2p8R6LeMTM6cQcssBNIAz1AgMBAAECggEACvuER5HezMWxWDsvEn0ca1emakcvjHxkH4jzj0TlCml9D5vBkZVTSRAtiZUqFzcXmr6qTixVMNlP8A0q7bpMTqWfuXNQvct2PHEEYUcYaA/zwZe7JMwPkjXZHxRFx5zbxF0d9FYAMpvB36ebcnbBrrv2PnlctXHu/Np2/4ct5P/8inYOKsCjWyWiXc6m+vTcEWuUslFK+EXuODgbWAeD/kH6GVw591KLsYtGm4J+tjU7b83cZtTSwRBSS47m8l9V6Clj6sGMeSTbcci9RMlxlkJV988nvy2I9yApdFhZYsC5e65CPpMgyQwly6/MFHdEAwGqIgZ67JzngwyGEzu2LQKBgQDYiCIYF3n82eixzhkYZZVehMIU3yfxtr84RV41fRrLxyD5SrXhSGzYlyq8x/P4C7bycjdBKz3jZ/f3QZ6bQUpyKbbZlkAGNkrPSal4I9GtiK9dl1kpI2ez1D+NTxs4jdHaBneO41BiHzflIIUZLqVokAeLhZOyJPqqVdZZnoaMuwKBgQDPs5WiQ/D0JWs0bB9O2ktZsIkPg+508IPkQZOCzccoqhyAQCWx3b9/YVz8118y4I7BPFTGKoQ6QiKOLK+CivlKmNt1/JzioMSS0dPQNPtcwWVNIqkthtBYCppipcc5g9IqvaBPIFz3wFkRy8aYrQ7m8HFYToa5XAaWT9NFRJOKDwKBgH3XYDkwK61P13S+mscbApxT+5e1ubk4xBkn94COnKwhpoA7c0jilp+p+ySL8LYP2Ns+le5B+03Wdr40XMFOSL3gkGwUblEt7HxcCynPZ2S7M2/pLeBCu1o/2E9/0gokIfncGE/qvCTKj+mR5Mil22vRiREFKLk31JZ6HIEpKHlRAoGAeweBRxC/lZ7klbTGBss/fu0XAt2/Tf154qcFXaHOqsO5Bi75JRaY7DccAZkBhv2FEelOveNJV8j89wJ5I/Z2HD3XIVh1Bbj9N4qa0OXRTuwvuTUiyRjmKIB/WpegHu16fF6+qqAc8ZQ6LlRqWt2Hyjv6p7g8DWf5bufx2UH5svkCgYBBVE7CFnfkUJWhrN3o+9nseJxn7NkjgPVUmItrpyYrxmmvCubpaGTmo/e7rFTt8Jd9EObHfXbF49HhwRqpWa4murFZ2Ca9dPhflJFChgiFBKZAqL4AglcbOU6PdHJKR8lW7KiX/NbY8x+IFsIY6kvTTr59zH2s16EJ6a3m84LdPw==',
	'jinli'=>'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAIX7ygU233CP2fVO6cmaevxbY38S27mWxfcAiErmzdLA/0M4JvXOb8qygtBwLe8hrJ0AFxHIwIOMAhVkbSHyAaz3YCeRiIkVH5BCTrCVLSh5b+SRWbPrj3+2qkJ9FsaS/l03ZJS6XxXMr6BUPMC3rRi8LJWpP/VvY9uW1S20cKY1AgMBAAECgYARKiy7dkgx13wI3U8+MLhI1Dxu7y+PGy3JcxwC3IbJ1UfeiLcVDplr0mrH4VdBJ4NBqd1KvflL4QfzTaZdju9/1WV+ncVmVoO4OQ2hELYHZ38JXOYPxanc0frTuLdxO//PSfR21Gpu8GzLlo72Z4QKh6QbLBCgwdP4AKO5uO7CgQJBANMJXV/CkZnFer8//QbLa1D9u0WNa7QdzgABE4LKhaPyYl7nNnnZtwmdVKjRlbWnRMX6Lp1WfrvYPi9O6buLf6kCQQCih7JP5cuzJewkmgpShpFy5AxkkvBpZidpl1M28FssPYS7WR8MZ4JJuf58oQvU8aRIr3NcDDKxcyqeaoIIAPmtAkB2J9ccp15H3xZDf/sV08ypEvbIEU8NEGbm/7NB1kwOp8XF5uRMQsZFXs4omvecNiO+SL3Sn7vjRkZCzIb21zrJAkBQLC/BSdGZpXM++t1sqATHb8bNNc5xr3pxk7vwtc/DmvUGlYfDTqvuQllOkQKNIEWxtRpqpXm8Hts/Gbrax+BhAkBxqFoDjymUziyCkF/OLWMiOQLAGjIy7LVSyFByq+ByZpDaCcPhko3YHcrUHOkqyDCMJVd1vQaw7TX9NvXVbE9C',
	'kupai'=>'MIICXAIBAAKBgQC430XGF7DZX/owNljDS+E8cwvulRnrF4jQYlSbqo0eNxdr2H7/jANIwBFFIlpFVCJfIo9X2jV1CfaT0j+jzun3PX8l5ezlxq+HIKYrSXyZnE5LezXuGIMWZgAnYnFM4zrPBOvuc5/RCVURPULmuLugrLWQia4u1YUYfPHB7kNj5wIDAQABAoGBAIykv24+o7obvDhlgA3Dcm0MkS1GnVsuolT2Gav7ijRMcTMIl0VOfUkhZYIU6lwH78Y0gpyxUy3hEIfQ0b2LMo4sQkD//LrtidMJNqRQt2e82i7QU8LcbygPvXybdd1/xlZ9b5ZQ1vVRR3+gdt2dInAYwrIDFsn7Y9WxYF+ZWMVBAkEA3/z5wZB2N/y5MJZnu8/s6Wqlx1+6UtdQQXMM6g7e2JUiMccKIi/wsynv/J0WNwjrhaG9PtQmZnIOth0kppajVwJBANNLKhEBTXEekOxdx/jymFLNE85MIzJcF88QDdL0wrExfQiDNyWh6soVJjPUStU6KW88G3m3TrHQii+1i46M+fECQA2oQyJGSK8JPdF0mkadWviwhAMwFxBOCJq1BSiQV44lKbyXQkrrWWXTPMrOWThp1tKDFiLqlJzSf4sjI8T0kmkCQHf9KlFn3PONOR2RkDs2gPwci8/OevphRAAJjOAssNdxVCDeaxXK4ouKARzTxP9bvSX3C19Okrj1xVOclDkKo/ECQET3jluvoTB5xn98Lchy9LFWifIgfu9Yx1KQGPo+90mUeYmwkpP/ZnxDywjj3qw00At0OeQY7EtkbsTvaXTtJWI=',
);
$pubkey = array
(
	'huawei'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr635Uc1JFbaGdmEOydZRIi75L0ao0DIGnmdLDtZXj2+AmYN6ncRxnx13xK7v1xV7xaf2TgVVfZAzkpiGJfFBewc1fdjFhWrpz9cPs7xfLJLz6Xk0NS/y5ShiyKnDIfgWkiUCxxLXXEmDHmXLRG77aCPNaiMQu96JBAb9MA/VxYNoxtiSWtfb7TTgYaY8/EJifyDo5S4ZxR+moLVVE0MuufhzkKBsEWXUoiyhvWLte1vcnJHqQqbou5qjKoWCal5fkFo0VugbzvUtKQ9gD70BReOxa+kxIeUTWPRXloUzly1v8+DAQt9cgjW6HXIbuUv9qfEei3jEzOnEHLLATSAM9QIDAQAB',
	'oppo'	=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmreYIkPwVovKR8rLHWlFVw7YDfm9uQOJKL89Smt6ypXGVdrAKKl0wNYc3/jecAoPi2ylChfa2iRu5gunJyNmpWZzlCNRIau55fxGW0XEu553IiprOZcaw5OuYGlf60ga8QT6qToP0/dpiL/ZbmNUO9kUhosIjEu22uFgR+5cYyQIDAQAB',
	'jinli'	=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCUqmbVA2Tqx2Mkg/jxk+x/hIJ8zw0t5Si9YkcmdkFXI0YuAU3p/x+b/SQvaEvGEAZt9RQVnwLP0OOxHQ6orRxGbRpsBKnUavqdO35MHsZuk7F0Tnk3UuGCW70llvNq6If6ekQYr05L+U1KjS1rPPWZv9xwpRTRyfzhHZW/lmDBEQIDAQAB',
	'wandou'=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCd95FnJFhPinpNiE/h4VA6bU1rzRa5+a25BxsnFX8TzquWxqDCoe4xG6QKXMXuKvV57tTRpzRo2jeto40eHKClzEgjx9lTYVb2RFHHFWio/YGTfnqIPTVpi7d7uHY+0FZ0lYL5LlW4E2+CQMxFOPRwfqGzMjs1SDlH7lVrLEVy6QIDAQAB',
	'kupai'=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCvenhwhkMlO9OaukAhUNoVHEq2VSGqWWUytmFIywNaKr7rV67lBfSLMuPhToIitSMW1pSXjiUpwRDyBOEOgJ3VLjrhet2nIWrLptnnfdMRU9hp0bZtB3JFmje0fdQhOzoKG2BLwv3Z0Xfu0hIi+Kq6N4530CgEiuWR11bPm7ms+wIDAQAB',
);

if($privkey)
{
	foreach($privkey as $k=>$value)
	{
		$d = \rsa::formatPriKey($value);
		echo $k.'-privkey'.PHP_EOL;
		file_put_contents($target_dir.$k.'_rsa_private_key.pem',$d);
	}
}

if($pubkey)
{
	foreach($pubkey as $k=>$value)
	{
		echo $k.'-pubkey'.PHP_EOL;
		$d = \rsa::formatPubKey($value);
		file_put_contents($target_dir.$k.'_rsa_public_key.pem',$d);
	}
}




