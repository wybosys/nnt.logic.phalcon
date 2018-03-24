<?php

use App\Controller\Api;

class ManagerController extends Api
{
    private function remote_command(string $cmd)
    {
        $key = new \phpseclib\Crypt\RSA();
        $key->loadKey(file_get_contents('manager/ssh/id_rsa'));
        $ssh = new \phpseclib\Net\SSH2('develop.91egame.com', 2222);
        if (!$ssh->login('wybo', $key))
            throw new \Exception('ssh failed');
        return $ssh->exec($cmd);
    }

    /**
     * @Action(\Manager\Model\BaseImages)
     * @param \Manager\Model\BaseImages $mdl
     */
    function baseimages(\Manager\Model\BaseImages $mdl)
    {
        $msg = $this->remote_command('docker image ls --format "{{.Repository}}\t{{.Tag}}"');
        $items = explode("\n", $msg);
        foreach ($items as $each) {
            $strs = explode("\t", $each);
            if (count($strs) != 2 || $strs[1] != 'latest')
                continue;
            if (!preg_match('/localhost:5000\/91egame\/service\/(.+)/', $strs[0], $res))
                continue;
            $mdl->images[] = $res[1];
        }
    }

    /**
     * @Action(\Manager\Model\Images)
     */
    function images(\Manager\Model\Images $mdl)
    {
        foreach (\Manager\Db\Images::query()->execute() as $each) {
            $img = new \Manager\Model\Image();
            $img->id = $each->id;
            $img->repo = $each->repo;
            $img->tag = $each->tag;
            $img->name = $each->name;
            $img->code = $each->code;
            array_push($mdl->items, $img);
        }
    }

    /**
     * @Action(\Manager\Model\NewImage)
     */
    function newimage(\Manager\Model\NewImage $mdl)
    {
        $name = 'localhost:5000/devops/service/' . md5($mdl->name);
        $image = 'localhost:5000/91egame/service/' . $mdl->image;
        // 禁止覆盖
        $fnd = \Manager\Db\Images::findFirst(["name = '$mdl->name'"]);
        if ($fnd)
            throw new \Exception("已经存在", \App\Model\Code::TARGET_EXISTS);

        // 生成dockerfile文件
        $dir = 'd:/storage/devops/docker/' . uniqid('tmp', false);
        $exp[] = 'mkdir ' . $dir;
        $dockerfile[] = "from $image";
        $dockerfile[] = "env PROJECT $mdl->code";
        $exp[] = 'echo -e "' . implode('\n', $dockerfile) . '" > ' . $dir . '/Dockerfile';
        $exp[] = "docker build $dir -t $name";
        $exp[] = "rm -rf $dir";
        $msg = $this->remote_command(implode(';', $exp));
        if (!preg_match('/Successfully built (.{12})/', $msg, $res))
            throw new \Exception($msg);

        // 写入到数据库中
        $rcd = new \Manager\Db\Images();
        $rcd->id = $res[1];
        $rcd->repo = $image;
        $rcd->tag = 'latest';
        $rcd->name = $mdl->name;
        $rcd->code = $mdl->code;
        $rcd->volume = $mdl->volume;
        $rcd->save();

        // 返回数据
        $mdl->id = $rcd->id;
    }

    /**
     * @Action(\Manager\Model\Image)
     */
    function delimage(\Manager\Model\Image $mdl)
    {
        $fnd = \Manager\Db\Images::findFirst(["id = '$mdl->id'"]);
        if (!$fnd)
            throw new \Exception("没有找到", \App\Model\Code::TARGET_NOT_FOUND);
        $exp = "docker image rm $fnd->id";
        $this->remote_command($exp);
        $fnd->delete();
    }

    /**
     * @Action(\Manager\Model\Image)
     */
    function queryimage(\Manager\Model\Image $mdl)
    {
        $fnd = \Manager\Db\Images::findFirst(["id = '$mdl->id'"]);
        if (!$fnd)
            throw new \Exception("没有找到", \App\Model\Code::TARGET_NOT_FOUND);
        $mdl->name = $fnd->name;
        $mdl->repo = $fnd->repo;
        $mdl->tag = $fnd->tag;
        $mdl->code = $fnd->code;
        $mdl->volume = $fnd->volume;
    }

    /**
     * @Action(\Manager\Model\Containers)
     */
    function containers(\Manager\Model\Containers $mdl)
    {
        foreach (\Manager\Db\Containers::query()->execute() as $each) {
            $con = new \Manager\Model\Container();
            $con->id = $each->id;
            $con->image = $each->image;
            $con->names = $each->names;
            array_push($mdl->items, $con);
        }
    }

    /**
     * @Action(\Manager\Model\StartContainer)
     */
    function startcontainer(\Manager\Model\StartContainer $mdl)
    {
        // 不能启动同名的
        if ($mdl->names) {
            $fndcon = \Manager\Db\Containers::findFirst(["names = '$mdl->names'"]);
            if ($fndcon)
                throw new \Exception("已经存在", \App\Model\Code::TARGET_EXISTS);
        }

        // 只能启动通过portal生成的
        $fndimg = \Manager\Db\Images::findFirst(["id = '$mdl->image'"]);
        if (!$fndimg)
            throw new \Exception("没有找到镜像", \App\Model\Code::TARGET_NOT_FOUND);

        // 启动docker
        $exp = '';
        if ($fndimg->volume)
            $exp .= 'mkdir -p d:/storage/devops/' . $fndimg->volume . ';';
        $exp .= 'docker run -dti --network internal';
        if ($mdl->names)
            $exp .= ' --name ' . $mdl->names;
        if ($fndimg->volume)
            $exp .= ' -v d:/storage/devops/' . $fndimg->volume . ':/data';
        $exp .= ' ' . $mdl->image;

        $msg = $this->remote_command($exp);
        if (!preg_match('/([a-z0-9]{64})/', $msg))
            throw new \Exception($msg, \App\Model\Code::FAILED);

        $rcd = new \Manager\Db\Containers();
        $rcd->id = substr($msg, 0, 12);
        $rcd->image = $mdl->image;
        $rcd->names = $mdl->names;
        $rcd->save();

        $mdl->id = $rcd->id;
    }

    /**
     * @Action(\Manager\Model\Container)
     * @param \Manager\Model\Container $mdl
     */
    function stopcontainer(\Manager\Model\Container $mdl)
    {
        $fnd = \Manager\Db\Containers::findFirst(["id = '$mdl->id'"]);
        if (!$fnd)
            throw new \Exception("没有找到", \App\Model\Code::TARGET_NOT_FOUND);
        $exp = "docker container rm -f $fnd->id";
        $this->remote_command($exp);
        $fnd->delete();
    }

    /**
     * @Action(\Manager\Model\Container)
     * @param \Manager\Model\Container $mdl
     */
    function restartcontainer(\Manager\Model\Container $mdl)
    {
        $fnd = \Manager\Db\Containers::findFirst(["id = '$mdl->id'"]);
        if (!$fnd)
            throw new \Exception("没有找到", \App\Model\Code::TARGET_NOT_FOUND);
        $exp = "docker restart $fnd->id";
        $this->remote_command($exp);
    }

    /**
     * @Action(\Manager\Model\ContainerOutput)
     * @param \Manager\Model\ContainerOutput $mdl
     */
    function containerlogs(\Manager\Model\ContainerOutput $mdl)
    {
        $fnd = \Manager\Db\Containers::findFirst(["id = '$mdl->id'"]);
        if (!$fnd)
            throw new \Exception("没有找到", \App\Model\Code::TARGET_NOT_FOUND);
        $exp = "docker container logs $fnd->id";
        $mdl->output = $this->remote_command($exp);
    }
}
